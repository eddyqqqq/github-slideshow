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
    data = json.dumps(design_svg.data(), separators=(',', ':'))
    with open(os.path.join(plugin, 'assets', 'shapes.js'), 'w') as f:
        f.write('/* Aries shapes, from the design SVGs (tools/design_svg.py) — Imagient */\nwindow.ARIES_SHAPES=' + data + ';\n')
    for f in ('gsap.min.js', 'ScrollTrigger.min.js', 'lenis.min.js'):
        copy(os.path.join(ROOT, 'vendor', f), os.path.join(plugin, 'assets', 'vendor', f))
    for f in os.listdir(os.path.join(ROOT, 'design', 'logos')):
        copy(os.path.join(ROOT, 'design', 'logos', f), os.path.join(plugin, 'assets', 'logos', f))
    fonts = os.path.join(ROOT, 'fonts')
    if os.path.isdir(fonts):
        for f in os.listdir(fonts):
            copy(os.path.join(fonts, f), os.path.join(theme, 'assets', 'fonts', f))
    else:
        print('warning: fonts/ missing, the theme will use the fallback font')
    for f in ('hero.webp', 'hero-900.webp', 'hero-900.avif', 'card-stacker.webp', 'card-vessel.webp'):
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


if __name__ == '__main__':
    main()
