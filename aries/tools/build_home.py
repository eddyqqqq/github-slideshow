"""Build aries/index.html (the homepage from the design) from tools/template_home.html.

Shape data comes from the design SVGs in design/ribbons/ (see design_svg.py); the logo mark is inlined.
The first prototype is still built by tools/build.py into v1/index.html.

Usage: python3 tools/build_home.py
"""
import base64
import json
import os
import re
import sys
sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))

import design_svg

HERE = os.path.dirname(os.path.abspath(__file__))


def logo(name):
    """A design logo as inline SVG: no fixed size (CSS sets the width), no clip wrapper (it only repeats the
    viewBox, and its id would clash when a logo is used twice)."""
    svg = open(os.path.join(HERE, '..', 'design', 'logos', name + '.svg')).read().strip()
    svg = re.sub(r'<g clip-path="url\(#[^)]+\)">|</g>\s*<defs>.*?</defs>', '', svg, flags=re.S)
    svg = re.sub(r'\s(width|height)="[\d.]+"', '', svg, count=2)
    return svg.replace('<svg ', '<svg class="logo" aria-hidden="true" focusable="false" ', 1).replace('\n', '')


def main():
    data = design_svg.data()
    payload = json.dumps(data, separators=(',', ':'))
    tpl = open(os.path.join(HERE, 'template_home.html')).read()
    mark = open(os.path.join(HERE, '..', 'img', 'mark.png'), 'rb').read()
    html = tpl.replace('/*SHAPES*/', payload).replace('/*MARK*/', 'data:image/png;base64,' + base64.b64encode(mark).decode())
    if os.environ.get('MENU') == '1':
        # the full-screen menu is kept aside for now (tools/parts/menu.html); MENU=1 puts it back
        part = open(os.path.join(HERE, 'parts', 'menu.html')).read()
        css = part[part.index('<style>') + 7:part.index('</style>')]
        markup = part[part.index('</style>') + 8:].strip()
        html = html.replace('</style>', css + '</style>', 1).replace('</header>', '</header>\n' + markup, 1)
    html = re.sub(r'<!--LOGO:(\w+)-->', lambda m: logo(m.group(1)), html)
    if os.environ.get('VARIANT') == 'contained':
        # the contained layout: same page, every section inside a centred frame (see .contained in the CSS)
        html = html.replace('<html lang="en">', '<html lang="en" class="contained">', 1).replace('<title>Aries Group</title>', '<title>Aries Group (contained)</title>', 1)
        open(os.path.join(HERE, '..', 'contained.html'), 'w').write(html)
        print('wrote contained.html')
        return
    open(os.path.join(HERE, '..', 'index.html'), 'w').write(html)
    pts = sum(len(v['lo']['x']) * 2 for v in data['ribbons'].values()) + len(data['hero']['x'])
    print(f'shape data {len(payload) // 1024} KB, points {pts}')


if __name__ == '__main__':
    main()
