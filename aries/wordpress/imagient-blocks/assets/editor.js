/*
 * Imagient Blocks — editor (Imagient · https://imagient.ro)
 * Plain JavaScript on the WordPress globals: no build step. Block attributes, titles and categories come from the
 * PHP registration; this file adds the editing UI. Every block is dynamic (rendered in PHP), so `save` returns
 * only the inner blocks, if any.
 */
(function (wp) {
  const el = wp.element.createElement, Fragment = wp.element.Fragment;
  const { registerBlockType } = wp.blocks;
  const { useBlockProps, useInnerBlocksProps, InspectorControls, InnerBlocks, RichText, MediaUpload, MediaUploadCheck, BlockControls } = wp.blockEditor;
  const { PanelBody, SelectControl, RangeControl, ToggleControl, TextControl, Button, ButtonGroup, ColorPalette, BaseControl, Placeholder, ToolbarGroup, ToolbarButton } = wp.components;
  const { useState } = wp.element;
  const { addFilter } = wp.hooks;
  const { createHigherOrderComponent } = wp.compose;
  const CFG = window.IMAGIENT || { logos: {}, palette: [], spaces: [], assets: '' };
  const PAL = CFG.palette.map(c => ({ name: c.name, color: c.color, slug: c.slug }));
  const colorOf = v => { const c = CFG.palette.find(p => p.slug === v); return c ? c.color : (v || ''); };
  const slugOf = hex => { const c = CFG.palette.find(p => p.color.toLowerCase() === String(hex || '').toLowerCase()); return c ? c.slug : (hex || ''); };
  const SPACES = CFG.spaces.map(s => ({ value: s.value, label: s.label }));
  const SPACE_PX = ['0px', '4px', '8px', '16px', '24px', '40px', '64px', '96px', '128px', '160px'];
  const space = v => `var(--ig-s${parseInt(v, 10) || 0}, ${SPACE_PX[parseInt(v, 10) || 0]})`;
  const LOGOS = Object.keys(CFG.logos).map(k => ({ value: k, label: CFG.logos[k] }));
  const SHAPES = [
    { value: 'group', label: 'Group (arc and peak)' }, { value: 'agency', label: 'Shipping Agency (wave)' },
    { value: 'interlink', label: 'Interlink (zigzag)' }, { value: 'logistics', label: 'Logistics (peak)' },
    { value: 'agencyT', label: 'Shipping Agency, tile' }, { value: 'interlinkT', label: 'Interlink, tile' }, { value: 'logisticsT', label: 'Logistics, tile' }
  ];
  const COLOR_OPTS = CFG.palette.map(c => ({ value: c.slug, label: c.name }));
  const icon = d => el('svg', { viewBox: '0 0 24 24', width: 24, height: 24 }, el('path', { d }));
  const ICONS = {
    section: icon('M3 5h18v14H3z M5 8h14v8H5z'),
    flex: icon('M3 6h5v12H3zM10 6h5v12h-5zM17 6h4v12h-4z'),
    grid: icon('M3 3h8v8H3zM13 3h8v8h-8zM3 13h8v8H3zM13 13h8v8h-8z'),
    item: icon('M4 4h16v16H4z'),
    box: icon('M4 4h16v16H4zM7 7v10h10V7z'),
    logo: icon('M4 3h16v8c-3 3-5 3-8 1-3 2-5 2-8-1z M4 15h16v2H4zM4 19h16v2H4z'),
    hero: icon('M2 9c5-6 9 4 20-4v5c-11 8-15-2-20 4z'),
    ribbon: icon('M2 14c6-8 12 2 20-6v4c-8 8-14-2-20 6z'),
    figures: icon('M3 3h18v8H3zM3 13h8v8H3zM13 13h8v8h-8z'),
    cards: icon('M2 5h13v14H2zM17 5h5v14h-5z'),
    scene: icon('M3 3h18v18H3zM5 14c4-5 8 1 14-4v3c-6 5-10-1-14 4z')
  };

  /* ---------------- responsive values: {d:{…}, t:{…}, m:{…}}, tablet falls back to desktop, mobile to tablet ------- */
  const BPS = [{ k: 'd', label: 'Desktop' }, { k: 't', label: 'Tablet' }, { k: 'm', label: 'Mobile' }];
  const rget = (o, bp, key, def) => {
    const order = bp === 'm' ? ['m', 't', 'd'] : bp === 't' ? ['t', 'd'] : ['d'];
    for (const b of order) { const v = o && o[b] && o[b][key]; if (v !== undefined && v !== '') return v; }
    return def;
  };
  const rset = (o, bp, key, val) => {
    const n = { d: { ...(o && o.d) }, t: { ...(o && o.t) }, m: { ...(o && o.m) } };
    if (val === undefined || (bp !== 'd' && val === '')) delete n[bp][key]; else n[bp][key] = val;
    return n;
  };
  const isOwn = (o, bp, key) => bp === 'd' || (o && o[bp] && o[bp][key] !== undefined && o[bp][key] !== '');

  function BreakpointTabs({ bp, setBp }) {
    return el('div', { className: 'ig-bp-tabs' },
      el(ButtonGroup, {}, BPS.map(b => el(Button, { key: b.k, isPressed: bp === b.k, variant: bp === b.k ? 'primary' : 'secondary', size: 'small', onClick: () => setBp(b.k) }, b.label))),
      el('p', { className: 'ig-bp-help' }, bp === 'd' ? 'Desktop values apply everywhere unless tablet or mobile change them.' : (bp === 't' ? 'Tablet (≤ 1024 px). Empty fields use the desktop value.' : 'Mobile (≤ 640 px). Empty fields use the tablet value.')));
  }
  // one responsive control: shows the inherited value and a reset when this breakpoint overrides it
  function RControl({ type, label, attr, setAttr, bp, k, def, options, min, max }) {
    const value = rget(attr, bp, k, def), own = isOwn(attr, bp, k);
    const set = v => setAttr(rset(attr, bp, k, v));
    const reset = !own || bp === 'd' ? null : el(Button, { variant: 'link', isDestructive: true, onClick: () => set(''), className: 'ig-reset' }, 'Use ' + (bp === 'm' ? 'tablet' : 'desktop'));
    let ctl;
    if (type === 'select') ctl = el(SelectControl, { label, value, options, onChange: set, __nextHasNoMarginBottom: true });
    else if (type === 'range') ctl = el(RangeControl, { label, value: parseInt(value, 10), min, max, onChange: v => set(String(v)), __nextHasNoMarginBottom: true });
    else if (type === 'toggle') ctl = el(ToggleControl, { label, checked: !!value, onChange: set, __nextHasNoMarginBottom: true });
    return el('div', { className: 'ig-rcontrol' + (own ? ' is-own' : ' is-inherited') }, ctl, reset);
  }
  const ColorControl = ({ label, value, onChange }) => el(BaseControl, { label, __nextHasNoMarginBottom: true },
    el(ColorPalette, { colors: PAL, value: colorOf(value), onChange: c => onChange(c ? slugOf(c) : '') }));

  const saveInner = () => el(InnerBlocks.Content);

  /* ================================ SECTION ================================ */
  registerBlockType('imagient/section', {
    icon: ICONS.section,
    edit({ attributes: a, setAttributes }) {
      const [bp, setBp] = useState('d');
      const sp = a.spacing;
      const style = { '--ig-pt-d': space(rget(sp, bp, 'pt', '7')), '--ig-pb-d': space(rget(sp, bp, 'pb', '7')), background: colorOf(a.background) || undefined };
      const props = useBlockProps({ className: 'ig-section tone-' + a.tone + (a.radius ? ' is-rounded' : '') + (a.minHeight === 'screen' ? ' is-screen' : ''), style });
      const inner = useInnerBlocksProps({ className: 'ig-container is-' + a.container }, { templateLock: false });
      return el(Fragment, {},
        el(InspectorControls, {},
          el(PanelBody, { title: 'Section', initialOpen: true },
            el(ColorControl, { label: 'Background', value: a.background, onChange: v => setAttributes({ background: v }) }),
            el(SelectControl, { label: 'Text tone', value: a.tone, options: [{ value: 'light', label: 'Dark text (light background)' }, { value: 'dark', label: 'White text (dark background)' }], onChange: v => setAttributes({ tone: v }) }),
            el(SelectControl, { label: 'Inner width', value: a.container, options: [{ value: 'gutter', label: 'Full width with page margins (design)' }, { value: 'wide', label: 'Contained, 1440 px' }, { value: 'narrow', label: 'Narrow, 960 px (text)' }, { value: 'full', label: 'Edge to edge, no margins' }], onChange: v => setAttributes({ container: v }) }),
            el(ToggleControl, { label: 'At least one screen tall', checked: a.minHeight === 'screen', onChange: v => setAttributes({ minHeight: v ? 'screen' : '' }) }),
            el(ToggleControl, { label: 'Rounded corners', checked: !!a.radius, onChange: v => setAttributes({ radius: v }) })),
          el(PanelBody, { title: 'Spacing', initialOpen: true },
            el(BreakpointTabs, { bp, setBp }),
            el(RControl, { type: 'select', label: 'Space above', attr: sp, setAttr: v => setAttributes({ spacing: v }), bp, k: 'pt', def: '7', options: SPACES }),
            el(RControl, { type: 'select', label: 'Space below', attr: sp, setAttr: v => setAttributes({ spacing: v }), bp, k: 'pb', def: '7', options: SPACES }))),
        el('section', props, el('div', inner)));
    },
    save: saveInner
  });

  /* ---------------- shared by Flex, Box and Grid: own value at a breakpoint, and what it inherits ---------------- */
  const UI = window.IMAGIENT_UI;
  const norm = v => v === true ? 'wrap' : v === false ? 'nowrap' : (v === undefined || v === null ? '' : String(v));
  const own = (l, bp, k) => norm(l && l[bp] && l[bp][k]);
  const inh = (l, bp, k, def) => { if (bp === 'd') return def; const o = bp === 'm' ? ['t', 'd'] : ['d']; for (const b of o) { const v = own(l, b, k); if (v) return v; } return def; };
  const eff = (l, bp, k, def) => own(l, bp, k) || inh(l, bp, k, def);
  const usedBps = l => ['d', 't', 'm'].filter(b => l && l[b] && Object.keys(l[b]).some(k => l[b][k] !== '' && l[b][k] !== undefined));
  const gapCss = v => /^\d$/.test(v) ? space(v) : v;
  const CHILD_FLEX = { equal: '1 1 0', auto: '0 1 auto', fill: '1 1 auto', none: '0 0 auto' };
  // set one or several keys at the current breakpoint (an empty value clears it)
  const layoutSetter = (l, bp, setAttributes) => obj => {
    let n = { d: { ...(l && l.d) }, t: { ...(l && l.t) }, m: { ...(l && l.m) } };
    Object.keys(obj).forEach(k => { const v = obj[k]; if (v === '' || v === undefined) delete n[bp][k]; else n[bp][k] = v; });
    setAttributes({ layout: n });
  };

  /* ================================ FLEX ================================ */
  const FLEX_DEF = { dir: 'row', justify: 'flex-start', align: 'stretch', gap: '4', wrap: 'nowrap', child: 'equal' };
  registerBlockType('imagient/flex', {
    icon: ICONS.flex,
    edit({ attributes: a, setAttributes }) {
      const [bp, setBp] = useState('d');
      const l = a.layout, E = k => eff(l, bp, k, FLEX_DEF[k]);
      const style = { '--ig-dir-d': E('dir'), '--ig-justify-d': E('justify'), '--ig-align-d': E('align'), '--ig-gap-d': gapCss(E('gap')), '--ig-wrap-d': E('wrap'), '--ig-child-d': /^column/.test(E('dir')) ? '0 0 auto' : (CHILD_FLEX[E('child')] || '1 1 0') };
      const props = useBlockProps({ className: 'ig-flex', style });
      const inner = useInnerBlocksProps(props, { orientation: /^row/.test(E('dir')) ? 'horizontal' : 'vertical', template: [['imagient/box'], ['imagient/box']] });
      const setMany = layoutSetter(l, bp, setAttributes);
      return el(Fragment, {},
        el(InspectorControls, {},
          el(PanelBody, { title: 'Flex layout', initialOpen: true, className: 'igl-panel' },
            el(UI.Devices, { bp, setBp, used: usedBps(l) }),
            el(UI.FlexControls, { get: k => own(l, bp, k), inh: k => inh(l, bp, k, FLEX_DEF[k]), set: (k, v) => setMany({ [k]: v }), setMany, child: true }))),
        el('div', inner));
    },
    save: saveInner
  });

  /* ================================ GRID ================================ */
  const GRID_DEF = { cols: '3', gap: '4', rowGap: '', align: 'stretch' };
  const GRID_ALIGN = [{ value: 'start', icon: 'a-flex-start', label: 'Top' }, { value: 'center', icon: 'a-center', label: 'Middle' }, { value: 'end', icon: 'a-flex-end', label: 'Bottom' }, { value: 'stretch', icon: 'a-stretch', label: 'Stretch' }];
  registerBlockType('imagient/grid', {
    icon: ICONS.grid,
    edit({ attributes: a, setAttributes }) {
      const [bp, setBp] = useState('d');
      const l = a.layout, E = k => eff(l, bp, k, GRID_DEF[k]);
      const style = { '--ig-cols-d': parseInt(E('cols'), 10) || 1, '--ig-gap-d': gapCss(E('gap')), '--ig-rgap-d': gapCss(E('rowGap') || E('gap')), '--ig-align-d': E('align') };
      const props = useBlockProps({ className: 'ig-grid', style });
      const inner = useInnerBlocksProps(props, { renderAppender: InnerBlocks.ButtonBlockAppender, template: [['imagient/grid-item', {}, [['imagient/box']]], ['imagient/grid-item', {}, [['imagient/box']]], ['imagient/grid-item', {}, [['imagient/box']]]] });
      const setMany = layoutSetter(l, bp, setAttributes);
      const cols = own(l, bp, 'cols');
      return el(Fragment, {},
        el(InspectorControls, {},
          el(PanelBody, { title: 'Grid layout', initialOpen: true, className: 'igl-panel' },
            el(UI.Devices, { bp, setBp, used: usedBps(l) }),
            el('div', { className: 'igl-row' }, el('span', { className: 'igl-label' + (cols ? ' is-set' : '') }, 'Columns'),
              el('div', { className: 'igl-gap' }, ['1', '2', '3', '4', '5', '6', '12'].map(c => el('button', { key: c, type: 'button', className: 'igl-chip' + (cols === c ? ' is-active' : '') + (!cols && inh(l, bp, 'cols', '3') === c ? ' is-inherited' : ''), onClick: () => setMany({ cols: cols === c ? '' : c }) }, c)))),
            el(UI.Icons, { label: 'Align items', value: own(l, bp, 'align'), inherit: inh(l, bp, 'align', 'stretch'), options: GRID_ALIGN, onChange: v => setMany({ align: v }) }),
            el(UI.Gap, { label: 'Column gap', value: own(l, bp, 'gap'), inherit: inh(l, bp, 'gap', '4'), onChange: v => setMany({ gap: v }) }),
            el(UI.Gap, { label: 'Row gap', value: own(l, bp, 'rowGap'), inherit: inh(l, bp, 'rowGap', '') || inh(l, bp, 'gap', '4'), onChange: v => setMany({ rowGap: v }) }))),
        el('div', inner));
    },
    save: saveInner
  });

  registerBlockType('imagient/grid-item', {
    icon: ICONS.item,
    edit({ attributes: a, setAttributes }) {
      const [bp, setBp] = useState('d');
      const l = a.layout, E = (k, d) => eff(l, bp, k, d);
      const props = useBlockProps({ className: 'ig-grid-item', style: { '--ig-span-d': parseInt(E('span', '1'), 10), '--ig-rows-d': parseInt(E('rows', '1'), 10), '--ig-self-d': E('align', '') || 'auto' } });
      const inner = useInnerBlocksProps(props, {});
      const setMany = layoutSetter(l, bp, setAttributes);
      const chips = (k, list, def) => el('div', { className: 'igl-gap' }, list.map(c => el('button', { key: c, type: 'button', className: 'igl-chip' + (own(l, bp, k) === c ? ' is-active' : '') + (!own(l, bp, k) && inh(l, bp, k, def) === c ? ' is-inherited' : ''), onClick: () => setMany({ [k]: own(l, bp, k) === c ? '' : c }) }, c)));
      return el(Fragment, {},
        el(InspectorControls, {},
          el(PanelBody, { title: 'Grid item', initialOpen: true, className: 'igl-panel' },
            el(UI.Devices, { bp, setBp, used: usedBps(l) }),
            el('div', { className: 'igl-row' }, el('span', { className: 'igl-label' }, 'Column span'), chips('span', ['1', '2', '3', '4', '6', '12'], '1')),
            el('div', { className: 'igl-row' }, el('span', { className: 'igl-label' }, 'Row span'), chips('rows', ['1', '2', '3', '4'], '1')),
            el(UI.Icons, { label: 'Vertical align', value: own(l, bp, 'align'), inherit: inh(l, bp, 'align', ''), options: GRID_ALIGN, onChange: v => setMany({ align: v }) }))),
        el('div', inner));
    },
    save: saveInner
  });

  /* ================================ BOX ================================ */
  const BOX_DEF = { dir: 'column', justify: 'flex-start', align: 'stretch', gap: '3', pad: '5', wrap: 'nowrap' };
  registerBlockType('imagient/box', {
    icon: ICONS.box,
    edit({ attributes: a, setAttributes }) {
      const [bp, setBp] = useState('d');
      const l = a.layout, E = k => eff(l, bp, k, BOX_DEF[k]);
      const style = { '--ig-pad-d': gapCss(E('pad')), '--ig-dir-d': E('dir'), '--ig-justify-d': E('justify'), '--ig-align-d': E('align'), '--ig-gap-d': gapCss(E('gap')), '--ig-radius': (parseInt(a.radius, 10) || 0) + 'px', background: colorOf(a.background) || undefined, minHeight: a.minHeight ? parseInt(a.minHeight, 10) + 'px' : undefined };
      const props = useBlockProps({ className: 'ig-box tone-' + a.tone, style });
      const inner = useInnerBlocksProps(props, { template: [['core/heading', { level: 3 }], ['core/paragraph']] });
      const setMany = layoutSetter(l, bp, setAttributes);
      return el(Fragment, {},
        el(InspectorControls, {},
          el(PanelBody, { title: 'Box', initialOpen: true },
            el(ColorControl, { label: 'Background', value: a.background, onChange: v => setAttributes({ background: v }) }),
            el(SelectControl, { label: 'Text tone', value: a.tone, options: [{ value: 'light', label: 'Dark text' }, { value: 'dark', label: 'White text' }], onChange: v => setAttributes({ tone: v }) }),
            el(RangeControl, { label: 'Corner radius (px)', value: parseInt(a.radius, 10) || 0, min: 0, max: 40, onChange: v => setAttributes({ radius: String(v) }) }),
            el(RangeControl, { label: 'Minimum height (px, 0 = auto)', value: parseInt(a.minHeight, 10) || 0, min: 0, max: 900, step: 10, onChange: v => setAttributes({ minHeight: v ? String(v) : '' }) }),
            el(TextControl, { label: 'Link (the whole box)', value: a.link, onChange: v => setAttributes({ link: v }), placeholder: 'https://… or #section' })),
          el(PanelBody, { title: 'Layout', initialOpen: true, className: 'igl-panel' },
            el(UI.Devices, { bp, setBp, used: usedBps(l) }),
            el(UI.Gap, { label: 'Padding', value: own(l, bp, 'pad'), inherit: inh(l, bp, 'pad', '5'), onChange: v => setMany({ pad: v }) }),
            el(UI.FlexControls, { get: k => own(l, bp, k), inh: k => inh(l, bp, k, BOX_DEF[k]), set: (k, v) => setMany({ [k]: v }), setMany, hide: ['wrap'] }))),
        el('div', inner));
    },
    save: saveInner
  });

  /* ================================ LOGO ================================ */
  registerBlockType('imagient/logo', {
    icon: ICONS.logo,
    edit({ attributes: a, setAttributes }) {
      const props = useBlockProps({ className: 'ig-logo' + (a.white ? ' is-white' : '') });
      return el(Fragment, {},
        el(InspectorControls, {},
          el(PanelBody, { title: 'Logo', initialOpen: true },
            el(SelectControl, { label: 'Logo', value: a.logo, options: LOGOS, onChange: v => setAttributes({ logo: v }) }),
            el(ToggleControl, { label: 'White (on colour)', checked: a.white, onChange: v => setAttributes({ white: v }) }),
            el(RangeControl, { label: 'Width at 1440 px (px)', value: a.width, min: 30, max: 200, onChange: v => setAttributes({ width: v }) }),
            el(ToggleControl, { label: 'Links to the home page', checked: a.link, onChange: v => setAttributes({ link: v }) }))),
        el('div', props, el('img', { src: CFG.assets + 'logos/' + a.logo + '.svg', alt: '', style: { width: a.width + 'px', display: 'block', filter: a.white ? 'brightness(0) invert(1)' : 'none' } })));
    },
    save: () => null
  });

  /* ---------------- ribbon preview in the editor (static, fully drawn) ---------------- */
  function bandD(xs, t, b) { const T = xs.map((x, k) => x + ' ' + t[k]), B = xs.map((x, k) => x + ' ' + b[k]).reverse(); return 'M' + T.join('L') + 'L' + B.join('L') + 'Z'; }
  function RibbonPreview({ shape, place, opacity }) {
    const S = window.ARIES_SHAPES && window.ARIES_SHAPES.ribbons[shape];
    if (!S) return null;
    // place: w = ribbon width / box width, x = left edge / box width, y = centre / box height (box = 1000 × 600)
    const W = 1000, H = 600, s = place.w * W / S.w, vbW = W / s, vbH = H / s;
    const gid = 'igp' + shape + Math.round(place.w * 100) + Math.round(place.x * 100);
    const grad = (id, g) => el('linearGradient', { id, gradientUnits: 'userSpaceOnUse', x1: g.c[0], y1: g.c[1], x2: g.c[2], y2: g.c[3] }, g.s.map((st, i) => el('stop', { key: i, offset: st[0], stopColor: '#fff', stopOpacity: st[1] })));
    return el('svg', { className: 'rib-svg ig-ribbon-preview', viewBox: `${-place.x * vbW} ${-place.y * vbH} ${vbW} ${vbH}`, preserveAspectRatio: 'xMidYMid slice', style: { opacity: (opacity === undefined ? 100 : opacity) / 100 }, 'aria-hidden': true },
      el('defs', {}, grad(gid + 'u', S.gu), grad(gid + 'l', S.gl)),
      el('path', { d: bandD(S.up.x, S.up.t, S.up.b), fill: `url(#${gid}u)` }),
      el('path', { d: bandD(S.lo.x, S.lo.t, S.lo.b), fill: `url(#${gid}l)` }));
  }
  const TILE_PLACE = { agencyT: { w: 1.545, x: -0.272, y: 0.508 }, interlinkT: { w: 2.966, x: -1.092, y: 0.433 }, logisticsT: { w: 3.217, x: -1.106, y: 0.722 } };

  /* ================================ RIBBON ================================ */
  registerBlockType('imagient/ribbon', {
    icon: ICONS.ribbon,
    edit({ attributes: a, setAttributes }) {
      const props = useBlockProps({ className: 'ig-ribbon-edit' });
      return el(Fragment, {},
        el(InspectorControls, {},
          el(PanelBody, { title: 'Ribbon', initialOpen: true },
            el(SelectControl, { label: 'Shape', value: a.shape, options: SHAPES, onChange: v => setAttributes({ shape: v }) }),
            el(RangeControl, { label: 'Width (% of the box)', value: a.size, min: 20, max: 400, onChange: v => setAttributes({ size: v }) }),
            el(RangeControl, { label: 'Left edge (% of the box)', value: a.x, min: -300, max: 80, onChange: v => setAttributes({ x: v }) }),
            el(RangeControl, { label: 'Vertical centre (% of the box)', value: a.y, min: -30, max: 130, onChange: v => setAttributes({ y: v }) }),
            el(RangeControl, { label: 'Opacity (%)', value: a.opacity, min: 5, max: 100, onChange: v => setAttributes({ opacity: v }) }),
            el(ToggleControl, { label: 'Drift sideways with the scroll', checked: a.pan, onChange: v => setAttributes({ pan: v }) }),
            el('p', { className: 'ig-help' }, 'Place it first inside a Section or Box with a coloured background: it fills the box and sits behind the content.'))),
        el('div', props, el(RibbonPreview, { shape: a.shape, place: { w: a.size / 100, x: a.x / 100, y: a.y / 100 }, opacity: a.opacity }), el('span', { className: 'ig-ribbon-label' }, 'Ribbon · ' + a.shape)));
    },
    save: () => null
  });

  /* ---------------- shared bits for the Aries scenes ---------------- */
  function ImagePicker({ id, url, onSelect, label }) {
    return el(MediaUploadCheck, {}, el(MediaUpload, {
      onSelect: m => onSelect(m), allowedTypes: ['image'], value: id,
      render: ({ open }) => el('div', { className: 'ig-image-picker' },
        url ? el('img', { src: url, alt: '', onClick: open }) : null,
        el(Button, { variant: 'secondary', onClick: open }, url ? 'Replace ' + (label || 'image') : 'Choose ' + (label || 'image')))
    }));
  }
  const listOps = (list, setList) => ({
    update: (i, patch) => setList(list.map((it, j) => j === i ? { ...it, ...patch } : it)),
    remove: i => setList(list.filter((_, j) => j !== i)),
    move: (i, d) => { const n = list.slice(); const j = i + d; if (j < 0 || j >= n.length) return; [n[i], n[j]] = [n[j], n[i]]; setList(n); },
    add: item => setList(list.concat([item]))
  });
  const ItemTools = ({ i, n, ops, label }) => el('div', { className: 'ig-item-tools' },
    el(Button, { size: 'small', icon: 'arrow-left-alt2', label: 'Move ' + label + ' earlier', disabled: i === 0, onClick: () => ops.move(i, -1) }),
    el(Button, { size: 'small', icon: 'arrow-right-alt2', label: 'Move ' + label + ' later', disabled: i === n - 1, onClick: () => ops.move(i, 1) }),
    el(Button, { size: 'small', icon: 'trash', label: 'Remove ' + label, isDestructive: true, onClick: () => ops.remove(i) }));
  const RT = (props) => el(RichText, Object.assign({ allowedFormats: ['core/bold', 'core/italic', 'core/link'] }, props));

  /* ================================ HERO ================================ */
  registerBlockType('imagient/hero', {
    icon: ICONS.hero,
    edit({ attributes: a, setAttributes }) {
      const props = useBlockProps({ className: 'ig-hero-edit' });
      const pick = m => setAttributes({ imageId: m.id, imageUrl: m.url });
      return el(Fragment, {},
        el(InspectorControls, {},
          el(PanelBody, { title: 'Photo', initialOpen: true },
            el(ImagePicker, { id: a.imageId, url: a.imageUrl, onSelect: pick, label: 'photo' }),
            el('p', { className: 'ig-help' }, 'Upload the photo large (3000 px wide or more): WordPress makes the smaller sizes and the browser picks the one the screen needs.'))),
        el('div', props,
          el('div', { className: 'ig-hero-edit__bands' }, a.imageUrl ? el('img', { src: a.imageUrl, alt: '' }) : null, el('span', { className: 'ig-badge' }, 'Frame + photo · animated on the site')),
          el('div', { className: 'ig-hero-edit__title' },
            RT({ tagName: 'h1', value: a.title, onChange: v => setAttributes({ title: v }), placeholder: 'Title' }),
            RT({ tagName: 'p', value: a.subtitle, onChange: v => setAttributes({ subtitle: v }), placeholder: 'Subtitle' })),
          el('div', { className: 'ig-hero-edit__scene' },
            el('span', { className: 'ig-badge' }, 'Over the photo, on scroll'),
            RT({ tagName: 'h2', value: a.sceneTitle, onChange: v => setAttributes({ sceneTitle: v }), placeholder: 'Title over the photo' }),
            RT({ tagName: 'p', value: a.sceneText, onChange: v => setAttributes({ sceneText: v }), placeholder: 'Text over the photo' }))));
    },
    save: () => null
  });

  /* ================================ COMPANY SCENE ================================ */
  registerBlockType('imagient/identity', {
    icon: ICONS.scene,
    edit({ attributes: a, setAttributes }) {
      const props = useBlockProps({ className: 'ig-scene-edit' });
      const list = a.companies || [], ops = listOps(list, v => setAttributes({ companies: v }));
      const [sel, setSel] = useState(0);
      const c = list[sel];
      return el(Fragment, {},
        el(InspectorControls, {},
          el(PanelBody, { title: 'Scene', initialOpen: true },
            el(TextControl, { label: 'Anchor (for links: #anchor, #anchor-1 …)', value: a.anchor, onChange: v => setAttributes({ anchor: v.replace(/[^\w-]/g, '') }) })),
          c ? el(PanelBody, { title: 'Company ' + (sel + 1), initialOpen: true },
            el(SelectControl, { label: 'Logo', value: c.logo, options: LOGOS, onChange: v => ops.update(sel, { logo: v }) }),
            el(SelectControl, { label: 'Ribbon shape', value: c.shape, options: SHAPES.slice(0, 4), onChange: v => ops.update(sel, { shape: v }) }),
            el(SelectControl, { label: 'Background colour', value: c.color, options: COLOR_OPTS, onChange: v => ops.update(sel, { color: v }) }),
            el(TextControl, { label: 'Link text', value: c.linkText, onChange: v => ops.update(sel, { linkText: v }) }),
            el(TextControl, { label: 'Link URL', value: c.linkUrl, onChange: v => ops.update(sel, { linkUrl: v }) })) : null),
        el('div', props,
          el('div', { className: 'ig-scene-edit__state', style: { background: colorOf('group') } },
            el(RibbonPreview, { shape: 'group', place: { w: 1.1, x: -0.05, y: 0.55 } }),
            el('span', { className: 'ig-badge' }, 'Group'),
            RT({ tagName: 'h2', value: a.introTitle, onChange: v => setAttributes({ introTitle: v }), placeholder: 'Group title' }),
            RT({ tagName: 'p', value: a.introText, onChange: v => setAttributes({ introText: v }), placeholder: 'Group text' }),
            RT({ tagName: 'p', className: 'ig-foot', value: a.footText, onChange: v => setAttributes({ footText: v }), placeholder: 'Bottom text' })),
          list.map((co, i) => el('div', { key: i, className: 'ig-scene-edit__state' + (i === sel ? ' is-selected' : ''), style: { background: colorOf(co.color) }, onClick: () => setSel(i) },
            el(RibbonPreview, { shape: co.shape, place: { w: 1.1, x: -0.05, y: 0.5 } }),
            el('img', { className: 'ig-scene-edit__logo', src: CFG.assets + 'logos/' + co.logo + '.svg', alt: '' }),
            el(ItemTools, { i, n: list.length, ops, label: 'company' }),
            RT({ tagName: 'h2', value: co.name, onChange: v => ops.update(i, { name: v }), placeholder: 'Company name' }),
            RT({ tagName: 'p', value: co.text, onChange: v => ops.update(i, { text: v }), placeholder: 'Description' }),
            co.linkText ? el('span', { className: 'ig-more' }, co.linkText) : null)),
          el(Button, { variant: 'secondary', className: 'ig-add', onClick: () => { ops.add({ name: 'New company', text: '', linkText: 'View more', linkUrl: '#', logo: 'group', shape: 'group', color: 'group' }); setSel(list.length); } }, '+ Add company')));
    },
    save: () => null
  });

  /* ================================ FIGURES ================================ */
  registerBlockType('imagient/figures', {
    icon: ICONS.figures,
    edit({ attributes: a, setAttributes }) {
      const props = useBlockProps({ className: 'figs ig-figures-edit' });
      const list = a.tiles || [], ops = listOps(list, v => setAttributes({ tiles: v }));
      const [sel, setSel] = useState(0);
      const t = list[sel];
      const figOps = i => listOps(list[i].figures || [], v => ops.update(i, { figures: v }));
      return el(Fragment, {},
        el(InspectorControls, {},
          t ? el(PanelBody, { title: 'Tile ' + (sel + 1), initialOpen: true },
            el(SelectControl, { label: 'Colour', value: t.color, options: COLOR_OPTS, onChange: v => ops.update(sel, { color: v }) }),
            el(SelectControl, { label: 'Ribbon', value: t.shape, options: SHAPES.slice(4), onChange: v => ops.update(sel, { shape: v }) }),
            el(ToggleControl, { label: 'Full width (two columns)', checked: !!t.wide, onChange: v => ops.update(sel, { wide: v }) }),
            el('p', { className: 'ig-help' }, 'Figures count from 0 when the tile comes into view. Write them as they should end, e.g. 800,000.')) : null),
        el('div', props, el('div', { className: 'wrap' },
          list.map((tile, i) => el('article', { key: i, className: 'tile' + (tile.wide ? ' wide' : '') + (i === sel ? ' is-selected' : ''), style: { background: colorOf(tile.color) }, onClick: () => setSel(i) },
            el(RibbonPreview, { shape: tile.shape, place: TILE_PLACE[tile.shape] || { w: 1.5, x: -0.25, y: 0.5 } }),
            el(ItemTools, { i, n: list.length, ops, label: 'tile' }),
            RT({ tagName: 'span', className: 'lbl', value: tile.label, onChange: v => ops.update(i, { label: v }), placeholder: 'Label' }),
            el('div', { className: 'nums' },
              (tile.figures || []).map((f, j) => el('div', { key: j, className: 'ig-fig' },
                el('strong', {}, RT({ tagName: 'span', value: f.value, allowedFormats: [], onChange: v => figOps(i).update(j, { value: v }), placeholder: '000' }),
                  el('sup', {}, RT({ tagName: 'span', value: f.suffix, allowedFormats: [], onChange: v => figOps(i).update(j, { suffix: v }), placeholder: '+' }))),
                RT({ tagName: 'small', value: f.caption, onChange: v => figOps(i).update(j, { caption: v }), placeholder: 'Caption' }),
                el(Button, { size: 'small', isDestructive: true, variant: 'link', onClick: () => figOps(i).remove(j) }, 'Remove figure'))),
              el(Button, { size: 'small', variant: 'secondary', onClick: () => figOps(i).add({ value: '100', suffix: '+', caption: '' }) }, '+ Figure')))),
          el(Button, { variant: 'secondary', className: 'ig-add', onClick: () => { ops.add({ label: 'New tile', shape: 'agencyT', color: 'agency', wide: false, figures: [{ value: '100', suffix: '+', caption: '' }] }); setSel(list.length); } }, '+ Add tile'))));
    },
    save: () => null
  });

  /* ================================ SERVICE CARDS ================================ */
  registerBlockType('imagient/cards', {
    icon: ICONS.cards,
    edit({ attributes: a, setAttributes }) {
      const props = useBlockProps({ className: 'ig-cards-edit' });
      const list = a.cards || [], ops = listOps(list, v => setAttributes({ cards: v }));
      const [sel, setSel] = useState(0);
      const c = list[sel];
      return el(Fragment, {},
        el(InspectorControls, {},
          c ? el(PanelBody, { title: 'Card ' + (sel + 1), initialOpen: true },
            el(ImagePicker, { id: c.imageId, url: c.imageUrl, onSelect: m => ops.update(sel, { imageId: m.id, imageUrl: m.url, alt: m.alt || c.alt }), label: 'photo' }),
            el(TextControl, { label: 'Image description (alt)', value: c.alt, onChange: v => ops.update(sel, { alt: v }) }),
            el(TextControl, { label: 'Link (optional)', value: c.url, onChange: v => ops.update(sel, { url: v }) })) : null),
        el('div', props, el('div', { className: 'ig-cards-edit__row' },
          list.map((card, i) => el('div', { key: i, className: 'card' + (i === sel ? ' is-selected' : ''), onClick: () => setSel(i) },
            RT({ tagName: 'h3', value: card.title, onChange: v => ops.update(i, { title: v }), placeholder: 'Card title' }),
            el('div', { className: 'pi' }, card.imageUrl ? el('img', { src: card.imageUrl, alt: '' }) : null), el('div', { className: 'bl' }),
            el(ItemTools, { i, n: list.length, ops, label: 'card' }))),
          el(Button, { variant: 'secondary', className: 'ig-add', onClick: () => { ops.add({ title: 'New service.', imageId: 0, imageUrl: '', alt: '', url: '' }); setSel(list.length); } }, '+ Add card'))));
    },
    save: () => null
  });

  /* ================================ COMPANY PAGES ================================ */
  // a logo inline, so the editor can colour its mark (.s) and name (.t) the way the page does
  const logoCache = {};
  function InlineLogo({ name, className }) {
    const [svg, setSvg] = useState(logoCache[name] || '');
    wp.element.useEffect(() => {
      if (logoCache[name]) { setSvg(logoCache[name]); return; }
      if (!window.fetch) return;
      let live = true;
      window.fetch(CFG.assets + 'logos/' + name + '.svg').then(r => r.text()).then(t => { logoCache[name] = t; if (live) setSvg(t); }).catch(() => {});
      return () => { live = false; };
    }, [name]);
    return el(wp.element.RawHTML, { className }, svg);
  }
  const FRAMES = CFG.frames || {};
  const logoOpts = [{ value: '', label: 'No logo' }].concat(LOGOS);
  const colorPick = (label, value, onChange) => el(SelectControl, { label, value, options: COLOR_OPTS, onChange });

  /* ---------------- page hero ---------------- */
  registerBlockType('imagient/page-hero', {
    icon: ICONS.hero,
    edit({ attributes: a, setAttributes }) {
      const props = useBlockProps({ className: 'ig-phero-edit', style: { '--accent': colorOf(a.color) } });
      const f = FRAMES[a.frame] || FRAMES[Object.keys(FRAMES)[0]] || null;
      const frameOpts = Object.keys(FRAMES).map(k => ({ value: k, label: k.charAt(0).toUpperCase() + k.slice(1) }));
      // the second scene: what appears as the page holds (a statement and a link); empty, the hero just scrolls away
      const inner = useInnerBlocksProps({ className: 'ig-phero-edit__scene' }, { template: [['core/heading', { className: 'ig-page-statement' }], ['core/buttons', {}, [['core/button', { className: 'is-style-aries-link', text: 'Contact us' }]]]], templateLock: false });
      return el(Fragment, {},
        el(InspectorControls, {},
          el(PanelBody, { title: 'Scroll scene', initialOpen: true },
            el(SelectControl, { label: 'As the page holds', value: a.scene || '0', options: [
              { value: '0', label: 'None: the hero scrolls away as a page part' },
              { value: '1', label: '1 · The frame closes, the statement appears under it, the colour floods out' },
              { value: '2', label: '2 · The colour spreads out of the frame over the photo, the statement appears' }], onChange: v => setAttributes({ scene: v }) }),
            el('p', { className: 'ig-help' }, 'The blocks under the title (statement, link) are the second scene; with none there, the cover that follows the hero gives its text. Compare on the site without saving: add ?hdr=1 or ?hdr=2 to the address.')),
          el(PanelBody, { title: 'Photo', initialOpen: false },
            el(ImagePicker, { id: a.imageId, url: a.imageUrl, onSelect: m => setAttributes({ imageId: m.id, imageUrl: m.url }), label: 'photo' }),
            el(RangeControl, { label: 'Focus, left to right (%)', value: a.focusX, min: 0, max: 100, onChange: v => setAttributes({ focusX: v }) }),
            el(RangeControl, { label: 'Focus, top to bottom (%)', value: a.focusY, min: 0, max: 100, onChange: v => setAttributes({ focusY: v }) }),
            el(RangeControl, { label: 'Zoom (%)', value: a.zoom, min: 100, max: 250, onChange: v => setAttributes({ zoom: v }) }),
            el('p', { className: 'ig-help' }, 'Upload the photo large (3000 px wide or more); the browser picks the size each screen needs.')),
          el(PanelBody, { title: 'Frame, logo and colour', initialOpen: false },
            frameOpts.length > 1 ? el(SelectControl, { label: 'Frame', value: a.frame, options: frameOpts, onChange: v => setAttributes({ frame: v }) }) : null,
            colorPick('Frame colour', a.color, v => setAttributes({ color: v })),
            el(SelectControl, { label: 'Logo (top left)', value: a.logo, options: logoOpts, onChange: v => setAttributes({ logo: v }) }),
            el(RangeControl, { label: 'Phones: part of the frame shown (centre, in frame units)', value: a.portraitX, min: 400, max: 1600, step: 10, onChange: v => setAttributes({ portraitX: v }) }))),
        el('div', props,
          a.logo && CFG.logos[a.logo] ? el(InlineLogo, { name: a.logo, className: 'ig-phero-edit__logo' }) : null,
          f ? el('svg', { className: 'ig-phero-edit__svg', viewBox: `${f.x0} 0 ${f.x1 - f.x0} ${f.h}`, preserveAspectRatio: 'xMidYMid slice' },
            el('defs', {}, el('clipPath', { id: 'phe-w' }, el('path', { d: f.window }))),
            el('g', { clipPath: 'url(#phe-w)' }, a.imageUrl ? el('image', { href: a.imageUrl, x: f.x0, y: 0, width: f.x1 - f.x0, height: f.h, preserveAspectRatio: 'xMidYMid slice' }) : null),
            el('path', { d: f.path, fill: colorOf(a.color) })) : el('p', {}, 'No frame'),
          RT({ tagName: 'h1', className: 'ig-phero-edit__title', value: a.title, onChange: v => setAttributes({ title: v }), placeholder: 'Page title' }),
          el('span', { className: 'ig-badge' }, 'Second scene (appears as the page holds)'),
          el('div', inner)));
    },
    save: saveInner
  });

  /* ---------------- cover ---------------- */
  registerBlockType('imagient/cover', {
    icon: ICONS.section,
    edit({ attributes: a, setAttributes }) {
      const grad = a.overlay ? `linear-gradient(${a.angle}deg, ${colorOf(a.from)} 0%, ${colorOf(a.from)}b8 30%, ${colorOf(a.to)}29 64%, ${colorOf(a.to)}61 100%)` : 'none';
      const props = useBlockProps({ className: 'ig-cover-edit tone-' + a.tone, style: { '--cv-img': a.imageUrl ? `url("${a.imageUrl}")` : 'none', '--cv-grad': grad, '--cv-o': a.strength / 100, '--cv-pos': `${a.focusX}% ${a.focusY}%` } });
      const inner = useInnerBlocksProps({ className: 'ig-cover-edit__inner' }, { template: [['core/heading', { className: 'ig-page-statement' }], ['core/buttons', {}, [['core/button', { className: 'is-style-aries-link', text: 'Contact us' }]]]] });
      return el(Fragment, {},
        el(InspectorControls, {},
          el(PanelBody, { title: 'Photo', initialOpen: true },
            el(ImagePicker, { id: a.imageId, url: a.imageUrl, onSelect: m => setAttributes({ imageId: m.id, imageUrl: m.url, alt: m.alt || a.alt }), label: 'photo' }),
            el(TextControl, { label: 'Image description (alt)', value: a.alt, onChange: v => setAttributes({ alt: v }) }),
            el(SelectControl, { label: 'Height', value: a.height, options: [{ value: 'image', label: 'The photo’s own proportions' }, { value: 'screen', label: 'One screen (with text and gradient: plays like the home page’s second scene)' }, { value: 'auto', label: 'As tall as its content' }], onChange: v => setAttributes({ height: v }) }),
            el(RangeControl, { label: 'Focus, left to right (%)', value: a.focusX, min: 0, max: 100, onChange: v => setAttributes({ focusX: v }) }),
            el(RangeControl, { label: 'Focus, top to bottom (%)', value: a.focusY, min: 0, max: 100, onChange: v => setAttributes({ focusY: v }) }),
            el(ToggleControl, { label: 'Photo drifts with the scroll', checked: a.parallax, onChange: v => setAttributes({ parallax: v }) })),
          el(PanelBody, { title: 'Colour gradient', initialOpen: true },
            el(ToggleControl, { label: 'Gradient over the photo', checked: a.overlay, onChange: v => setAttributes({ overlay: v }) }),
            a.overlay ? el(Fragment, {},
              el(SelectControl, { label: 'Gradient style', value: a.shade || 'text', options: [{ value: 'text', label: 'Deep on the text side (text over the photo)' }, { value: 'tint', label: 'Light colour at both edges (a photo on its own)' }], onChange: v => setAttributes({ shade: v }) }),
              colorPick('From (where the text is)', a.from, v => setAttributes({ from: v })),
              colorPick('To', a.to, v => setAttributes({ to: v })),
              el(RangeControl, { label: 'Direction (degrees)', value: a.angle, min: 0, max: 360, onChange: v => setAttributes({ angle: v }) }),
              el(RangeControl, { label: 'Strength (%)', value: a.strength, min: 0, max: 100, onChange: v => setAttributes({ strength: v }) })) : null,
            el(SelectControl, { label: 'Text colour', value: a.tone, options: [{ value: 'dark', label: 'White (on a dark photo)' }, { value: 'light', label: 'Dark (on a light photo)' }], onChange: v => setAttributes({ tone: v }) }))),
        el('section', props, el('div', inner)));
    },
    save: saveInner
  });

  /* ---------------- services list ---------------- */
  registerBlockType('imagient/services', {
    icon: ICONS.cards,
    edit({ attributes: a, setAttributes }) {
      const props = useBlockProps({ className: 'ig-svc-edit', style: { '--accent': colorOf(a.color) } });
      const list = a.items || [], ops = listOps(list, v => setAttributes({ items: v }));
      return el(Fragment, {},
        el(InspectorControls, {},
          el(PanelBody, { title: 'Look', initialOpen: true },
            colorPick('Accent colour', a.color, v => setAttributes({ color: v })))),
        el('div', props,
          el('span', { className: 'ig-badge' }, 'Services'),
          list.map((it, i) => el('div', { key: i, className: 'ig-svc-edit__row' },
            RT({ tagName: 'h3', className: 'ig-svc__title', value: it.title, onChange: v => ops.update(i, { title: v }), placeholder: 'Service' }),
            el('svg', { className: 'ig-svc__wave', viewBox: '0 0 212 26' }, el('path', { d: 'M0.2 14.47C93.3 54.73 126.45-31.67 211.2 14.47' })),
            RT({ tagName: 'p', className: 'ig-svc__text', value: it.text, onChange: v => ops.update(i, { text: v }), placeholder: 'What it covers' }),
            el(ItemTools, { i, n: list.length, ops, label: 'service' }))),
          el(Button, { variant: 'secondary', className: 'ig-add', onClick: () => ops.add({ title: 'New service', text: '' }) }, '+ Add service')));
    },
    save: () => null
  });

  /* ---------------- menu ---------------- */
  registerBlockType('imagient/menu', {
    icon: icon('M3 6h18v2H3zM3 11h18v2H3zM3 16h18v2H3z'),
    edit({ attributes: a, setAttributes }) {
      const props = useBlockProps({ className: 'ig-menu-edit' });
      const list = a.items || [], ops = listOps(list, v => setAttributes({ items: v }));
      return el(Fragment, {},
        el(InspectorControls, {},
          el(PanelBody, { title: 'Links', initialOpen: true },
            list.map((it, i) => el('div', { key: i, className: 'ig-menu-edit__item' },
              el(TextControl, { label: 'Label ' + (i + 1), value: it.label, onChange: v => ops.update(i, { label: v }) }),
              el(TextControl, { label: 'Link', value: it.url, onChange: v => ops.update(i, { url: v }) }),
              el('div', { className: 'ig-menu-edit__tools' },
                el(Button, { size: 'small', icon: 'arrow-up-alt2', label: 'Move up', disabled: i === 0, onClick: () => ops.move(i, -1) }),
                el(Button, { size: 'small', icon: 'arrow-down-alt2', label: 'Move down', disabled: i === list.length - 1, onClick: () => ops.move(i, 1) }),
                el(Button, { size: 'small', icon: 'trash', label: 'Remove', isDestructive: true, onClick: () => ops.remove(i) })))),
            el(Button, { variant: 'secondary', onClick: () => ops.add({ label: 'New link', url: '#' }) }, '+ Add link')),
          el(PanelBody, { title: 'Look', initialOpen: false },
            colorPick('Menu colour', a.color, v => setAttributes({ color: v })),
            el(TextControl, { label: 'LinkedIn page (empty: none)', value: a.linkedin, onChange: v => setAttributes({ linkedin: v }) }))),
        el('div', props, el('span', { className: 'ig-menu-edit__btn', 'aria-hidden': true }, el('i'), el('i'), el('i')), el('span', { className: 'ig-menu-edit__label' }, 'Menu · ' + list.length + ' links (opens full screen on the site)')));
    },
    save: () => null
  });

  /* ================================ ANIMATION SETTING ON EVERY BLOCK ================================ */
  addFilter('blocks.registerBlockType', 'imagient/anim-attribute', (settings) => {
    settings.attributes = Object.assign({}, settings.attributes, { igAnim: { type: 'string', default: '' } });
    return settings;
  });
  const ANIMS = [{ value: '', label: 'None' }, { value: 'fade-up', label: 'Fade up' }, { value: 'split', label: 'Words rise one by one (headings)' }, { value: 'count', label: 'Numbers count up' }, { value: 'scale-in', label: 'Scale in' }];
  addFilter('editor.BlockEdit', 'imagient/anim-control', createHigherOrderComponent(BlockEdit => props => {
    if (!props.isSelected) return el(BlockEdit, props);
    return el(Fragment, {}, el(BlockEdit, props),
      el(InspectorControls, {}, el(PanelBody, { title: 'Animation (Imagient)', initialOpen: !!props.attributes.igAnim },
        el(SelectControl, { label: 'On scroll', value: props.attributes.igAnim || '', options: ANIMS, onChange: v => props.setAttributes({ igAnim: v }) }),
        el('p', { className: 'ig-help' }, 'Plays on the site as the block scrolls into view.'))));
  }, 'withImagientAnimation'));
  addFilter('editor.BlockListBlock', 'imagient/anim-badge', createHigherOrderComponent(BlockListBlock => props => {
    const a = props.attributes && props.attributes.igAnim;
    return a ? el(BlockListBlock, Object.assign({}, props, { className: [props.className, 'ig-has-anim'].filter(Boolean).join(' '), wrapperProps: Object.assign({}, props.wrapperProps, { 'data-ig-anim': a }) })) : el(BlockListBlock, props);
  }, 'withImagientAnimBadge'));
})(window.wp);
