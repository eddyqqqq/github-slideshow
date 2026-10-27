"""
The hero frame (2026 design): two bands closed by a bar at each end. The designer's vector is design/hero-frame.svg
(one closed outline, artboard 2008 × 878.48; the 1920 units between the bars are the 1920 px screen);
design/hero-frame.png is it rendered 4096 px wide, which this script traces into band edges.
Writes tools/hero_frame.json, which build_wp.py puts into shapes.js as ARIES_SHAPES.frame.

  python3 tools/hero_frame.py

Each band is given on one set of x positions: x, t (top edge), b (bottom edge), in artboard units. The photo
placement (rot, k = photo width in units / 1500, cx, cy) was fitted on the 1920 × 1080 mockup.
"""
import json
import os

import numpy as np
from PIL import Image

HERE = os.path.dirname(os.path.abspath(__file__))
SRC = os.path.join(HERE, '..', 'design', 'hero-frame.png')
OUT = os.path.join(HERE, 'hero_frame.json')
ART_W, ART_H = 2008.0, 878.48   # the viewBox of design/hero-frame.svg
PHOTO = {'rot': 20.7, 'k': 1.47, 'cx': 1066.1, 'cy': 478.0}   # fitted on the mockup (photo 1500 px wide)
TOP_1080 = 100.0                                               # artboard top on the 1920 × 1080 mockup


def runs(col):
    r, y, n = [], 0, len(col)
    while y < n:
        if col[y]:
            s = y
            while y < n and col[y]:
                y += 1
            r.append((s, y - 1))
        else:
            y += 1
    return r


def rdp(pts, eps):
    if len(pts) < 3:
        return pts
    a, b = pts[0], pts[-1]
    ab = b - a
    q = pts - a
    d = np.abs(ab[0] * q[:, 1] - ab[1] * q[:, 0]) / np.hypot(*ab)
    i = int(np.argmax(d))
    if d[i] > eps:
        return np.vstack([rdp(pts[:i + 1], eps)[:-1], rdp(pts[i:], eps)])
    return np.vstack([a, b])


def trace():
    al = np.array(Image.open(SRC))[:, :, 3].astype(float) / 255
    A = al > 0.5
    H, W = A.shape
    S = W / ART_W
    # the bars: columns that are one solid run
    cols = [len(runs(A[:, x])) for x in range(W)]
    x_in0 = next(x for x in range(W) if cols[x] == 2)
    x_in1 = max(x for x in range(W) if cols[x] == 2) + 1
    left = runs(A[:, 0])[0]
    right = runs(A[:, W - 1])[0]
    xs, E = [], []
    for x in range(x_in0 + 2, x_in1 - 2):
        r = runs(A[:, x])
        if len(r) != 2:
            continue
        (a1, b1), (a2, b2) = r
        a = al[:, x]
        # sub-pixel edges from the coverage of the pixel just outside each run
        t1 = a1 - a[a1 - 1] if a1 > 0 else a1
        e1 = b1 + 1 + a[b1 + 1]
        t2 = a2 - a[a2 - 1]
        e2 = b2 + 1 + (a[b2 + 1] if b2 + 1 < H else 0)
        xs.append((x + 0.5) / S)
        E.append([t1 / S, e1 / S, t2 / S, e2 / S])
    xs, E = np.array(xs), np.array(E)
    k = np.ones(15) / 15
    Es = np.vstack([np.convolve(np.pad(E[:, i], 7, mode='edge'), k, mode='valid') for i in range(4)]).T
    for i in range(4):   # keep the drawn corners sharp
        d2 = np.abs(np.gradient(np.gradient(E[:, i])))
        hard = np.convolve(d2 > 0.08, np.ones(9), mode='same') > 0
        Es[hard, i] = E[hard, i]
    bar0, bar1 = x_in0 / S, x_in1 / S
    bands = []
    for ti, bi in ((0, 1), (2, 3)):
        # one set of x for both edges: the union of each edge's simplified points
        keep = set()
        for i in (ti, bi):
            for x, _ in rdp(np.c_[xs, Es[:, i]], 0.4):
                keep.add(float(x))
        bx = np.array(sorted(keep))
        t = np.interp(bx, xs, Es[:, ti])
        b = np.interp(bx, xs, Es[:, bi])
        # the ends meet the bars' inner edges exactly
        bx[0], bx[-1] = bar0, bar1
        bands.append({'x': [round(v, 2) for v in bx], 't': [round(v, 2) for v in t], 'b': [round(v, 2) for v in b]})
    return {
        'w': ART_W, 'h': ART_H, 'bars': [round(bar0, 2), round(bar1, 2)],
        # outer corners of the bars: left (top, bottom) at x = 0, right (top, bottom) at x = w
        'left': [round(left[0] / S, 2), round((left[1] + 1) / S, 2)],
        'right': [round(right[0] / S, 2), round((right[1] + 1) / S, 2)],
        'a': bands[0], 'b': bands[1], 'photo': PHOTO, 'top1080': TOP_1080,
    }


if __name__ == '__main__':
    f = trace()
    with open(OUT, 'w') as fh:
        json.dump(f, fh, separators=(',', ':'))
    print('bars', f['bars'], 'left', f['left'], 'right', f['right'], 'points', len(f['a']['x']), len(f['b']['x']),
          'json', os.path.getsize(OUT), 'bytes')
