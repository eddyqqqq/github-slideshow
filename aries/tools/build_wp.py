"""Build the WordPress theme (Aries) and plugin (Imagient Blocks) into ready-to-upload zips.

- imagient-blocks/assets/shapes.js  ← the design SVGs (design_svg.py), as window.ARIES_SHAPES
- imagient-blocks/assets/vendor     ← GSAP, ScrollTrigger, Lenis (vendor/)
- imagient-blocks/assets/logos      ← design/logos/*.svg
- aries/assets/fonts                ← fonts/*.woff2 (licensed, not in git)
- aries/assets/images               ← img/ (default photos used by the patterns)
- dist/aries.zip, dist/imagient-blocks.zip

Usage: python3 tools/build_wp.py
"""
import json
import os
import re
import shutil
import sys
import zipfile

HERE = os.path.dirname(os.path.abspath(__file__))
ROOT = os.path.join(HERE, '..')
WP = os.path.join(ROOT, 'wordpress')
sys.path.insert(0, HERE)
import design_svg  # noqa: E402


def copy(src, dst):
    os.makedirs(os.path.dirname(dst), exist_ok=True)
    shutil.copyfile(src, dst)


def path_points(d):
    """Every end and control point of an SVG path (absolute), enough for the x extent of a frame's straight bars."""
    toks = re.findall(r'[MmLlHhVvCcSsQqTtZz]|-?(?:\d+\.?\d*|\.\d+)(?:e-?\d+)?', d)
    pts, i, cmd, x, y, sx, sy = [], 0, 'M', 0.0, 0.0, 0.0, 0.0
    sizes = {'M': 2, 'L': 2, 'H': 1, 'V': 1, 'C': 6, 'S': 4, 'Q': 4, 'T': 2, 'Z': 0}
    while i < len(toks):
        if re.match(r'[A-Za-z]', toks[i]):
            cmd = toks[i]; i += 1
            if cmd in 'Zz':
                x, y = sx, sy
                continue
        n = sizes[cmd.upper()]
        v = [float(t) for t in toks[i:i + n]]; i += n
        rel = cmd.islower()
        if cmd.upper() == 'H':
            x = x + v[0] if rel else v[0]
        elif cmd.upper() == 'V':
            y = y + v[0] if rel else v[0]
        else:
            ps = [(v[k] + (x if rel else 0), v[k + 1] + (y if rel else 0)) for k in range(0, n, 2)]
            pts += ps[:-1]
            x, y = ps[-1]
            if cmd.upper() == 'M':
                sx, sy = x, y
                cmd = 'l' if rel else 'L'
        pts.append((x, y))
    return pts


def frame_geometry(d, win_d, K=600):
    """PHP array for the page's scripts, per column across the whole frame (bars included): x, ot/ob (the frame's
    outer top and bottom) and t/b (the window's top and bottom; null where a column has no window, at the bars)."""
    from ribbons_svg import flatten_path
    subs = re.findall(r'M[^M]+', d)
    outer = flatten_path(subs[0], 96)
    inner = flatten_path(win_d, 96)

    def cross(pts, x):
        ys = []
        for (x1, y1), (x2, y2) in zip(pts, pts[1:] + pts[:1]):
            if (x1 <= x < x2) or (x2 <= x < x1):
                ys.append(y1 + (y2 - y1) * (x - x1) / (x2 - x1))
        return ys
    # the frame's ends and the window's ends are slightly slanted: there a column is only partly cut; the columns start
    # 5 units in from the frame's ends, and at the window's ends a partial column takes the next full one's edges
    fx0, fx1 = min(p[0] for p in outer) + 5, max(p[0] for p in outer) - 5
    cols = {'x': [], 'ot': [], 'ob': [], 't': [], 'b': []}
    win = []
    for k in range(K + 1):
        x = fx0 + (fx1 - fx0) * k / K
        oy, wy = cross(outer, x), cross(inner, x)
        if not oy:
            continue
        cols['x'].append(round(x, 1)); cols['ot'].append(round(min(oy), 1)); cols['ob'].append(round(max(oy), 1))
        win.append((min(wy), max(wy)) if len(wy) >= 2 else None)
    idx = [k for k, w in enumerate(win) if w]
    for ends in (idx, idx[::-1]):
        for a_, b_ in zip(ends, ends[1:]):
            if win[a_][1] - win[a_][0] >= 0.97 * (win[b_][1] - win[b_][0]):
                break
            win[a_] = win[b_]
    for w in win:
        cols['t'].append(round(w[0], 1) if w else 'null'); cols['b'].append(round(w[1], 1) if w else 'null')
    # the outline itself, as points (x, y, x, y …): the page moves these, so the frame keeps its exact shape (its slanted
    # bar ends included) while it closes
    for key, pts in (('o', flatten_path(subs[0], 24)), ('w', flatten_path(win_d, 24))):
        cols[key] = [round(v, 1) for p in pts for v in p]
    return 'array( ' + ', '.join("'%s' => array( %s )" % (k, ', '.join(str(v) for v in vals)) for k, vals in cols.items()) + ' )'


def frames_php(plugin):
    """inc/frames.php from design/frame-*.svg: each frame's outline, its photo window and the window's x extent."""
    out = []
    for f in sorted(os.listdir(os.path.join(ROOT, 'design'))):
        m = re.match(r'frame-([a-z]+)\.svg$', f)
        if not m:
            continue
        svg = open(os.path.join(ROOT, 'design', f)).read()
        vb = [float(v) for v in re.search(r'viewBox="([^"]+)"', svg).group(1).split()]
        d = re.sub(r'\s+', ' ', re.search(r' d="([^"]+)"', svg).group(1)).strip()
        subs = re.findall(r'M[^M]+', d)
        win = max(subs[1:], key=len) if len(subs) > 1 else subs[0]
        xs = [p[0] for p in path_points(win)]
        # the opening's sides can slant: take the innermost x of each side (+0.5), so no sliver of frame shows at the screen's edges
        lo, hi = min(xs), max(xs)
        x0, x1 = max(x for x in xs if x < lo + 20) + 0.5, min(x for x in xs if x > hi - 20) - 0.5
        # the frame's geometry for the page's scripts, so no browser has to measure the path (Safari is slow at it):
        # per column across the window, the window's top and bottom edges and the frame's outer top and bottom
        geo = frame_geometry(d, win)
        out.append("\t\t'%s' => array( 'w' => %s, 'h' => %s, 'x0' => %.2f, 'x1' => %.2f, 'path' => '%s', 'window' => '%s', 'geo' => %s )," % (
            m.group(1), vb[2], vb[3], x0, x1, d, win, geo))
    php = ("<?php\n/**\n * The company page frames (Imagient), generated by tools/build_wp.py from design/frame-*.svg: do not edit.\n"
           " * path: the outline as drawn; window: the photo's opening; x0, x1: the opening's left and right edge (the\n"
           " * 1920 px between them span the screen up to 1920 px wide).\n */\n\ndefined( 'ABSPATH' ) || exit;\n\n"
           "function imagient_frames() {\n\treturn array(\n" + "\n".join(out) + "\n\t);\n}\n")
    with open(os.path.join(plugin, 'inc', 'frames.php'), 'w') as fh:
        fh.write(php)


def check_css(path):
    """Stop the build on unbalanced braces: one stray } makes browsers drop the rule after it (Safari did)."""
    import re
    # comments blanked, their line breaks kept, so the reported line is the file's
    text = re.sub(r'/\*.*?\*/', lambda m: '\n' * m.group(0).count('\n'), open(path).read(), flags=re.S)
    text = re.sub(r'"[^"]*"|\'[^\']*\'', '', text)
    depth = 0
    for i, c in enumerate(text):
        depth += (c == '{') - (c == '}')
        if depth < 0:
            sys.exit('%s: stray } at line %d' % (path, text.count('\n', 0, i) + 1))
    if depth:
        sys.exit('%s: %d unclosed {' % (path, depth))


def main():
    plugin, theme = os.path.join(WP, 'imagient-blocks'), os.path.join(WP, 'aries')
    for css in (os.path.join(plugin, 'assets', 'aries.css'), os.path.join(plugin, 'assets', 'editor.css'), os.path.join(theme, 'style.css')):
        check_css(css)
    frames_php(plugin)
    shapes = design_svg.data()
    shapes.pop('hero', None)   # the first design's band: the static pages only
    data = json.dumps(shapes, separators=(',', ':'))
    with open(os.path.join(plugin, 'assets', 'shapes.js'), 'w') as f:
        f.write('/* Aries shapes, from the design SVGs (tools/design_svg.py) — Imagient */\nwindow.ARIES_SHAPES=' + data + ';\n')
    for f in ('gsap.min.js', 'ScrollTrigger.min.js', 'lenis.min.js'):
        copy(os.path.join(ROOT, 'vendor', f), os.path.join(plugin, 'assets', 'vendor', f))
    # the logos go inline into every page: their coordinates rounded to 0.01 of a design pixel (they show at most
    # ~90 px wide) and the white space between their tags dropped
    for f in os.listdir(os.path.join(ROOT, 'design', 'logos')):
        svg = open(os.path.join(ROOT, 'design', 'logos', f)).read()
        svg = re.sub(r'-?\d+\.\d{3,}', lambda m: ('%.2f' % float(m.group(0))).rstrip('0').rstrip('.'), svg)
        svg = re.sub(r'>\s+<', '><', svg).strip() + '\n'
        # the mark's paths (starting in its top two thirds) and the name's: a company logo, white in its file, can then
        # be shown in colour (the mark in the Group blue, the name in ink) by CSS
        svg = re.sub(r'<path d="M\s*[-\d.]+[ ,]([-\d.]+)', lambda m: '<path class="%s" d="M%s' % ('s' if float(m.group(1)) < 90 else 't', m.group(0)[len('<path d="M'):]), svg)
        os.makedirs(os.path.join(plugin, 'assets', 'logos'), exist_ok=True)
        with open(os.path.join(plugin, 'assets', 'logos', f), 'w') as out:
            out.write(svg)
    fonts = os.path.join(ROOT, 'fonts')
    if os.path.isdir(fonts):
        for f in os.listdir(fonts):
            copy(os.path.join(fonts, f), os.path.join(theme, 'assets', 'fonts', f))
    else:
        print('warning: fonts/ missing, the theme will use the fallback font')
    # the default photos, each as AVIF (served first) and WebP (older browsers); the 900 px hero is for the static pages
    images = os.path.join(theme, 'assets', 'images')
    for f in os.listdir(images):
        os.remove(os.path.join(images, f))
    photos = ('hero', 'card-stacker', 'card-vessel', 'shipping-agency-sea', 'shipping-agency-port')
    for f in sorted(os.listdir(os.path.join(ROOT, 'img'))):
        if not f.startswith(photos) or not f.endswith(('.avif', '.webp')) or f.startswith('hero-900'):
            continue
        copy(os.path.join(ROOT, 'img', f), os.path.join(theme, 'assets', 'images', f))

    dist = os.path.join(ROOT, 'dist')
    os.makedirs(dist, exist_ok=True)
    for name, src in (('imagient-blocks', plugin), ('aries', theme)):
        out = os.path.join(dist, name + '.zip')
        with zipfile.ZipFile(out, 'w', zipfile.ZIP_DEFLATED) as z:
            for base, _, files in os.walk(src):
                for f in files:
                    if f.startswith('.'):
                        continue
                    full = os.path.join(base, f)
                    z.write(full, os.path.join(name, os.path.relpath(full, src)))
        print(out, os.path.getsize(out) // 1024, 'KB')

    # deploy/manifest.json: every file of each package with its SHA-256, read by the Imagient Deploy plugin on the server
    # (it downloads from GitHub only what differs). Files kept out of the repository (licensed fonts) are left out.
    import hashlib, subprocess
    tracked = set(subprocess.run(['git', 'ls-files'], cwd=ROOT, capture_output=True, text=True).stdout.split())
    deployer = os.path.join(ROOT, 'wordpress', 'imagient-deploy')
    version = re.search(r'Version:\s*(\S+)', open(os.path.join(plugin, 'imagient-blocks.php')).read()).group(1)
    packages = {}
    for slug, kind, src in (('imagient-blocks', 'plugin', plugin), ('aries', 'theme', theme), ('imagient-deploy', 'plugin', deployer)):
        rel_src = os.path.relpath(src, ROOT)
        files = {}
        for base, _, names in os.walk(src):
            for f in sorted(names):
                full = os.path.join(base, f)
                rel = os.path.relpath(full, src).replace(os.sep, '/')
                if f.startswith('.') or rel.startswith('assets/fonts/'):
                    continue
                files[rel] = hashlib.sha256(open(full, 'rb').read()).hexdigest()
                if os.path.relpath(full, ROOT) not in tracked:
                    print('  (to commit)', os.path.relpath(full, ROOT))
        packages[slug] = {'type': kind, 'src': 'aries/' + rel_src.replace(os.sep, '/'), 'files': dict(sorted(files.items()))}
    os.makedirs(os.path.join(ROOT, 'deploy'), exist_ok=True)
    with open(os.path.join(ROOT, 'deploy', 'manifest.json'), 'w') as fh:
        json.dump({'version': version, 'packages': packages}, fh, indent=1)
        fh.write('\n')
    print('deploy/manifest.json', version, {k: len(v['files']) for k, v in packages.items()})
    # the deploy plugin itself, zipped once for its first install
    out = os.path.join(dist, 'imagient-deploy.zip')
    with zipfile.ZipFile(out, 'w', zipfile.ZIP_DEFLATED) as z:
        z.write(os.path.join(deployer, 'imagient-deploy.php'), 'imagient-deploy/imagient-deploy.php')


if __name__ == '__main__':
    main()
