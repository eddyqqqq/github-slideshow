/*
 * Imagient layout controls (Imagient · https://imagient.ro): icon pickers in the spirit of Webflow and Elementor V4.
 * Device switch, direction arrows, a 3 × 3 alignment matrix, justify / align / wrap / child-sizing icons and a gap
 * field with presets. Shared by the layout blocks (editor.js) and the Style tab (style-panel.js) via window.IMAGIENT_UI.
 */
(function (wp) {
  const el = wp.element.createElement;
  const { Button, Tooltip } = wp.components;

  /* ---------------- pictograms (24 × 24, drawn for a row; columns rotate them) ---------------- */
  const svg = (...kids) => el('svg', { viewBox: '0 0 24 24', width: 24, height: 24, 'aria-hidden': true, focusable: false }, ...kids);
  const r = (x, y, w, h, o) => el('rect', { x, y, width: w, height: h, rx: 1, fill: 'currentColor', opacity: o === undefined ? 1 : o });
  const frame = () => el('rect', { x: 2.5, y: 4.5, width: 19, height: 15, rx: 2, fill: 'none', stroke: 'currentColor', strokeOpacity: 0.35 });
  const arrow = d => svg(el('path', { d, fill: 'none', stroke: 'currentColor', strokeWidth: 2, strokeLinecap: 'round', strokeLinejoin: 'round' }));
  const ICON = {
    'row': arrow('M4 12h15M14 7l5 5-5 5'),
    'column': arrow('M12 4v15M7 14l5 5 5-5'),
    'row-reverse': arrow('M20 12H5M10 7l-5 5 5 5'),
    'column-reverse': arrow('M12 20V5M7 10l5-5 5 5'),
    // justify (main axis): three items placed along the row
    'j-flex-start': svg(frame(), r(4, 8, 3, 8), r(8, 8, 3, 8), r(12, 8, 3, 8)),
    'j-center': svg(frame(), r(6.5, 8, 3, 8), r(10.5, 8, 3, 8), r(14.5, 8, 3, 8)),
    'j-flex-end': svg(frame(), r(9, 8, 3, 8), r(13, 8, 3, 8), r(17, 8, 3, 8)),
    'j-space-between': svg(frame(), r(4, 8, 3, 8), r(10.5, 8, 3, 8), r(17, 8, 3, 8)),
    'j-space-around': svg(frame(), r(5.5, 8, 3, 8), r(10.5, 8, 3, 8), r(15.5, 8, 3, 8)),
    'j-space-evenly': svg(frame(), r(6, 8, 2.5, 8), r(10.75, 8, 2.5, 8), r(15.5, 8, 2.5, 8)),
    // align (cross axis): items of different heights
    'a-flex-start': svg(frame(), r(5, 6, 4, 6), r(10, 6, 4, 10), r(15, 6, 4, 4)),
    'a-center': svg(frame(), r(5, 9, 4, 6), r(10, 7, 4, 10), r(15, 10, 4, 4)),
    'a-flex-end': svg(frame(), r(5, 12, 4, 6), r(10, 8, 4, 10), r(15, 14, 4, 4)),
    'a-stretch': svg(frame(), r(5, 6, 4, 12), r(10, 6, 4, 12), r(15, 6, 4, 12)),
    'a-baseline': svg(frame(), r(5, 8, 4, 6), r(10, 6, 4, 8), r(15, 10, 4, 4), el('path', { d: 'M3 14h18', stroke: 'currentColor', strokeDasharray: '2 2', strokeWidth: 1 })),
    // wrap
    'nowrap': svg(frame(), r(4, 9, 4, 6), r(9, 9, 4, 6), r(14, 9, 4, 6), r(19, 9, 1.5, 6, 0.35)),
    'wrap': svg(frame(), r(4, 6.5, 4, 5), r(9, 6.5, 4, 5), r(14, 6.5, 4, 5), r(4, 12.5, 4, 5), r(9, 12.5, 4, 5, 0.5)),
    // child sizing
    'equal': svg(frame(), r(4, 8, 5, 8), r(9.5, 8, 5, 8), r(15, 8, 5, 8)),
    'auto': svg(frame(), r(4, 8, 3, 8), r(8, 8, 6, 8), r(15, 8, 2, 8)),
    'fill': svg(frame(), r(4, 8, 4, 8), r(9, 8, 11, 8)),
    'none': svg(frame(), r(4, 8, 3, 8), r(8, 8, 3, 8), r(12, 8, 3, 8), el('path', { d: 'M17 9v6', stroke: 'currentColor', strokeOpacity: 0.4 }))
  };

  /* ---------------- device switch ---------------- */
  const DEVICES = [{ k: 'd', label: 'Desktop', icon: 'desktop' }, { k: 't', label: 'Tablet (≤ 1024 px)', icon: 'tablet' }, { k: 'm', label: 'Mobile (≤ 640 px)', icon: 'smartphone' }];
  function Devices({ bp, setBp, used }) {
    return el('div', { className: 'igl-devices' },
      DEVICES.map(d => el(Tooltip, { key: d.k, text: d.label }, el(Button, { icon: d.icon, isPressed: bp === d.k, className: used && used.includes(d.k) ? 'has-values' : '', onClick: () => setBp(d.k), 'aria-label': d.label }))),
      el('span', { className: 'igl-device-note' }, bp === 'd' ? 'All screens' : bp === 't' ? 'Tablet: overrides Desktop' : 'Mobile: overrides Tablet'));
  }

  /* ---------------- a row of icon buttons; the inherited choice is outlined, the own one filled ---------------- */
  function Icons({ label, value, inherit, options, onChange, rotate }) {
    return el('div', { className: 'igl-row' },
      label ? el('span', { className: 'igl-label' + (value ? ' is-set' : '') }, label) : null,
      el('div', { className: 'igl-icons' + (rotate ? ' is-column' : '') }, options.map(o => el(Tooltip, { key: o.value, text: o.label },
        el(Button, { className: 'igl-icon' + (value === o.value ? ' is-active' : '') + (!value && inherit === o.value ? ' is-inherited' : ''), 'aria-pressed': value === o.value, 'aria-label': o.label,
          onClick: () => onChange(value === o.value ? '' : o.value) }, ICON[o.icon || o.value] || o.label)))));
  }

  /* ---------------- 3 × 3 alignment matrix (Webflow): sets justify and align for the current direction ---------------- */
  const POS = ['flex-start', 'center', 'flex-end'];
  function Matrix({ dir, justify, align, onChange }) {
    const col = /^column/.test(dir || 'row');
    return el('div', { className: 'igl-matrix-wrap' },
      el('div', { className: 'igl-matrix', role: 'group', 'aria-label': 'Alignment' },
        [0, 1, 2].map(y => [0, 1, 2].map(x => {
          // x = horizontal, y = vertical; a row maps x → justify, y → align; a column the other way round
          const j = col ? POS[y] : POS[x], a = col ? POS[x] : POS[y];
          const on = (justify || 'flex-start') === j && (align || 'stretch') === a;
          return el('button', { key: x + '-' + y, type: 'button', className: 'igl-cell' + (on ? ' is-on' : ''), 'aria-label': `${['Left', 'Centre', 'Right'][x]} ${['top', 'middle', 'bottom'][y]}`, onClick: () => onChange(j, a) }, el('span'));
        }))),
      el('span', { className: 'igl-matrix-help' }, 'Click where the content should sit.'));
  }

  /* ---------------- gap: presets + free value ---------------- */
  const GAPS = [{ v: '0', l: '0' }, { v: '2', l: '8' }, { v: '3', l: '16' }, { v: '4', l: '24' }, { v: '5', l: '40' }, { v: '6', l: '64' }];
  function Gap({ label, value, inherit, onChange }) {
    const shown = /^\d$/.test(value || '') ? '' : (value || '');
    const inhShown = /^\d$/.test(inherit || '') ? (GAPS.find(g => g.v === inherit) || {}).l + 'px' : inherit;
    return el('div', { className: 'igl-row' },
      el('span', { className: 'igl-label' + (value ? ' is-set' : '') }, label || 'Gap'),
      el('div', { className: 'igl-gap' },
        GAPS.map(g => el('button', { key: g.v, type: 'button', className: 'igl-chip' + (value === g.v ? ' is-active' : '') + (!value && inherit === g.v ? ' is-inherited' : ''), onClick: () => onChange(value === g.v ? '' : g.v) }, g.l)),
        el('input', { type: 'text', className: 'igl-input', value: shown, placeholder: inhShown || 'px, rem…', onChange: e => onChange(e.target.value),
          onBlur: e => { const v = e.target.value.trim(); if (/^\d+(\.\d+)?$/.test(v)) onChange(v + 'px'); } })));
  }

  const DIRS = [{ value: 'row', label: 'Row (side by side)' }, { value: 'column', label: 'Column (stacked)' }, { value: 'row-reverse', label: 'Row, reversed' }, { value: 'column-reverse', label: 'Column, reversed' }];
  const JUSTIFY = ['flex-start', 'center', 'flex-end', 'space-between', 'space-around', 'space-evenly'].map(v => ({ value: v, icon: 'j-' + v, label: { 'flex-start': 'Start', center: 'Centre', 'flex-end': 'End', 'space-between': 'Space between', 'space-around': 'Space around', 'space-evenly': 'Space evenly' }[v] }));
  const ALIGN = ['flex-start', 'center', 'flex-end', 'stretch', 'baseline'].map(v => ({ value: v, icon: 'a-' + v, label: { 'flex-start': 'Start', center: 'Centre', 'flex-end': 'End', stretch: 'Stretch', baseline: 'Baseline' }[v] }));
  const WRAP = [{ value: 'nowrap', label: 'One line' }, { value: 'wrap', label: 'Wrap onto new lines' }];
  const CHILD = [{ value: 'equal', label: 'Equal widths: children share the space evenly' }, { value: 'auto', label: 'Fit content: children as wide as their content' }, { value: 'fill', label: 'Fill: children grow from their content size' }, { value: 'none', label: 'Fixed: children keep their own width' }];

  /* ---------------- the whole flex panel; get(k) own value, inh(k) inherited value, set(k, v) ---------------- */
  function FlexControls({ get, inh, set, setMany, child, hide }) {
    const dir = get('dir') || inh('dir') || 'row', col = /^column/.test(dir);
    const H = hide || [];
    return el('div', { className: 'igl-flex' },
      el(Icons, { label: 'Direction', value: get('dir'), inherit: inh('dir'), options: DIRS, onChange: v => set('dir', v) }),
      el(Matrix, { dir, justify: get('justify') || inh('justify'), align: get('align') || inh('align'), onChange: (j, a) => setMany({ justify: j, align: a }) }),
      el(Icons, { label: col ? 'Justify (vertical)' : 'Justify (horizontal)', value: get('justify'), inherit: inh('justify'), options: JUSTIFY, onChange: v => set('justify', v), rotate: col }),
      el(Icons, { label: col ? 'Align (horizontal)' : 'Align (vertical)', value: get('align'), inherit: inh('align'), options: ALIGN, onChange: v => set('align', v), rotate: col }),
      H.includes('wrap') ? null : el(Icons, { label: 'Wrap', value: get('wrap'), inherit: inh('wrap'), options: WRAP, onChange: v => set('wrap', v) }),
      child && !col ? el(Icons, { label: 'Children', value: get('child'), inherit: inh('child'), options: CHILD, onChange: v => set('child', v) }) : null,
      el(Gap, { value: get('gap'), inherit: inh('gap'), onChange: v => set('gap', v) }));
  }

  window.IMAGIENT_UI = { ICON, Devices, Icons, Matrix, Gap, FlexControls, DIRS, JUSTIFY, ALIGN, WRAP, CHILD };
})(window.wp);
