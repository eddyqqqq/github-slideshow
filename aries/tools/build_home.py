"""Build aries/index.html (the homepage from the design) from tools/template_home.html.

Shape data comes from the design SVGs in design/ribbons/ (see design_svg.py); the logo mark is inlined.
The first prototype is still built by tools/build.py into v1/index.html.

Usage: python3 tools/build_home.py
"""
import base64
import json
import os
import sys
sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))

import design_svg

HERE = os.path.dirname(os.path.abspath(__file__))


def main():
    data = design_svg.data()
    payload = json.dumps(data, separators=(',', ':'))
    tpl = open(os.path.join(HERE, 'template_home.html')).read()
    mark = open(os.path.join(HERE, '..', 'img', 'mark.png'), 'rb').read()
    html = tpl.replace('/*SHAPES*/', payload).replace('/*MARK*/', 'data:image/png;base64,' + base64.b64encode(mark).decode())
    open(os.path.join(HERE, '..', 'index.html'), 'w').write(html)
    pts = sum(len(v['lo']['x']) * 2 for v in data['ribbons'].values()) + len(data['hero']['x'])
    print(f'shape data {len(payload) // 1024} KB, points {pts}')


if __name__ == '__main__':
    main()
