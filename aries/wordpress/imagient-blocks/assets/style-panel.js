/*
 * Imagient Style — the Style tab on every block (Imagient · https://imagient.ro).
 * Like Elementor's Style tab: Layout, Spacing, Size, Position, Typography, Background, Border, Effects and
 * Custom CSS, each value typed freely (24px, 2rem, 50%, clamp(…), #hex …), for Desktop / Tablet / Mobile and for
 * Hover. Saved in the block's `igStyle` attribute; the same rules are generated in PHP for the site and here for
 * the editor preview.
 */
(function (wp) {
  const el = wp.element.createElement, Fragment = wp.element.Fragment;
  const { useState } = wp.element;
  const { addFilter } = wp.hooks;
  const { createHigherOrderComponent } = wp.compose;
  const { InspectorControls, MediaUpload, MediaUploadCheck } = wp.blockEditor;
  const C = wp.components;
  const { PanelBody, SelectControl, TextControl, TextareaControl, Button, Dropdown, ColorPalette, ColorIndicator, Tooltip } = C;
  const CFG = window.IMAGIENT || { palette: [] };
  const PAL = (CFG.palette || []).map(c => ({ name: c.name, color: c.color, slug: c.slug }));

  /* ---------------- the CSS model (mirrors inc/style.php) ---------------- */
  const PROPS = {
    display: 'display', flexDirection: 'flex-direction', justifyContent: 'justify-content', alignItems: 'align-items', alignContent: 'align-content',
    alignSelf: 'align-self', flexWrap: 'flex-wrap', gap: 'gap', rowGap: 'row-gap', columnGap: 'column-gap', gridTemplateColumns: 'grid-template-columns',
    gridTemplateRows: 'grid-template-rows', gridColumn: 'grid-column', gridRow: 'grid-row', order: 'order', flex: 'flex',
    width: 'width', height: 'height', minWidth: 'min-width', maxWidth: 'max-width', minHeight: 'min-height', maxHeight: 'max-height', overflow: 'overflow',
    aspectRatio: 'aspect-ratio', objectFit: 'object-fit', objectPosition: 'object-position', position: 'position', zIndex: 'z-index',
    fontFamily: 'font-family', fontSize: 'font-size', fontWeight: 'font-weight', lineHeight: 'line-height', letterSpacing: 'letter-spacing',
    textTransform: 'text-transform', textDecoration: 'text-decoration', fontStyle: 'font-style', textAlign: 'text-align', color: 'color', whiteSpace: 'white-space',
    backgroundColor: 'background-color', backgroundSize: 'background-size', backgroundPosition: 'background-position', backgroundRepeat: 'background-repeat',
    backgroundAttachment: 'background-attachment', borderStyle: 'border-style', borderColor: 'border-color',
    opacity: 'opacity', boxShadow: 'box-shadow', transform: 'transform', transition: 'transition', filter: 'filter', backdropFilter: 'backdrop-filter',
    cursor: 'cursor', mixBlendMode: 'mix-blend-mode'
  };
  const SIDES = ['top', 'right', 'bottom', 'left'];
  const okVal = v => typeof v === 'string' && v.trim() !== '' && !/[{}<>;\\]|\/\*|expression\s*\(|javascript:|@import/i.test(v);
  const val = v => { const c = PAL.find(p => p.slug === v); return c ? `var(--wp--preset--color--${c.slug}, ${c.color})` : v.trim(); };
  function decls(set) {
    if (!set) return '';
    let out = '';
    Object.keys(set).forEach(k => { if (PROPS[k] && okVal(set[k])) out += PROPS[k] + ':' + val(set[k]) + ';'; });
    [['margin', s => 'margin-' + s], ['padding', s => 'padding-' + s], ['inset', s => s], ['borderWidth', s => 'border-' + s + '-width']].forEach(([k, f]) => {
      if (set[k]) SIDES.forEach(s => { if (okVal(set[k][s])) out += f(s) + ':' + val(set[k][s]) + ';'; });
    });
    if (set.borderRadius) [['top', 'top-left'], ['right', 'top-right'], ['bottom', 'bottom-right'], ['left', 'bottom-left']].forEach(([s, c]) => { if (okVal(set.borderRadius[s])) out += 'border-' + c + '-radius:' + val(set.borderRadius[s]) + ';'; });
    const layers = [];
    if (set.backgroundImage) layers.push('url("' + String(set.backgroundImage).replace(/"/g, '%22') + '")');
    if (okVal(set.backgroundGradient) && /gradient\(/i.test(set.backgroundGradient)) layers.push(set.backgroundGradient);
    if (layers.length) out += 'background-image:' + layers.join(',') + ';';
    return out;
  }
  function css(style, sel) {
    if (!style) return '';
    let out = '';
    const d = decls(style.d), h = decls(style.h), t = decls(style.t), m = decls(style.m);
    if (d) out += `${sel}{${d}}`;
    if (h) out += `${sel}:hover{${h}}`;
    if (t) out += `@media (max-width:1024px){${sel}{${t}}}`;
    if (m) out += `@media (max-width:640px){${sel}{${m}}}`;
    if (style.css) out += String(style.css).replace(/</g, '').replace(/selector/g, sel);
    return out;
  }
  const isEmpty = o => !o || Object.keys(o).every(k => { const v = o[k]; return v === '' || v === undefined || (typeof v === 'object' && isEmpty(v)); });

  /* ---------------- controls ---------------- */
  const STATES = [{ k: 'd', label: 'Desktop', icon: 'desktop' }, { k: 't', label: 'Tablet', icon: 'tablet' }, { k: 'm', label: 'Mobile', icon: 'smartphone' }];
  // what a field shows when empty at this breakpoint: the value it inherits
  const inherited = (style, bp, get) => {
    const order = bp === 'm' ? ['t', 'd'] : bp === 't' ? ['d'] : bp === 'h' ? ['d'] : [];
    for (const b of order) { const v = get(style[b] || {}); if (v) return v; }
    return '';
  };
  const Field = ({ label, children, set }) => el('div', { className: 'igs-field' + (set ? ' is-set' : '') }, el('span', { className: 'igs-label' }, label), el('div', { className: 'igs-input' }, children));

  function Value({ label, k, ctx, placeholder, suggest }) {
    const v = (ctx.cur[k] || '');
    const inh = inherited(ctx.style, ctx.bp, s => s[k]);
    return el(Field, { label, set: !!v },
      el('input', { type: 'text', className: 'igs-text', value: v, placeholder: inh || placeholder || '', list: suggest ? 'igs-' + suggest : undefined,
        onChange: e => ctx.setProp(k, e.target.value), onBlur: e => ctx.setProp(k, unitless(k, e.target.value)) }),
      v ? el(Button, { className: 'igs-clear', icon: 'no-alt', label: 'Clear', size: 'small', onClick: () => ctx.setProp(k, '') }) : null);
  }
  // a bare number in a length field means px (as in Elementor); line-height, opacity, z-index, order stay unitless
  const UNITLESS = ['lineHeight', 'opacity', 'zIndex', 'order', 'flex', 'fontWeight', 'aspectRatio'];
  const unitless = (k, v) => (/^-?\d+(\.\d+)?$/.test((v || '').trim()) && !UNITLESS.includes(k)) ? v.trim() + 'px' : v;

  function Select({ label, k, ctx, options }) {
    const v = ctx.cur[k] || '';
    const inh = inherited(ctx.style, ctx.bp, s => s[k]);
    const opts = [{ value: '', label: inh ? `Inherit (${inh})` : 'Default' }].concat(options.map(o => typeof o === 'string' ? { value: o, label: o } : o));
    return el(Field, { label, set: !!v }, el('select', { className: 'igs-select', value: v, onChange: e => ctx.setProp(k, e.target.value) }, opts.map(o => el('option', { key: o.value, value: o.value }, o.label))));
  }

  // four sides (or corners) with a link toggle, like Elementor's dimensions control
  function Sides({ label, k, ctx, corners }) {
    const cur = ctx.cur[k] || {};
    const inh = inherited(ctx.style, ctx.bp, s => s[k] && SIDES.some(x => s[k][x]) ? s[k] : null) || {};
    const [linked, setLinked] = useState(SIDES.every(s => (cur[s] || '') === (cur.top || '')) && !!cur.top);
    const set = (side, v) => { const n = { ...cur }; (linked ? SIDES : [side]).forEach(s => { n[s] = v; }); ctx.setProp(k, isEmpty(n) ? '' : n); };
    const names = corners ? ['↖', '↗', '↘', '↙'] : ['Top', 'Right', 'Bottom', 'Left'];
    return el(Field, { label, set: !isEmpty(cur) },
      el('div', { className: 'igs-sides' },
        SIDES.map((s, i) => el('label', { key: s }, el('input', { type: 'text', value: cur[s] || '', placeholder: inh[s] || '', onChange: e => set(s, e.target.value), onBlur: e => set(s, unitless(k, e.target.value)) }), el('span', {}, names[i]))),
        el(Tooltip, { text: linked ? 'Unlink sides' : 'Link sides' }, el(Button, { className: 'igs-link' + (linked ? ' is-linked' : ''), icon: linked ? 'admin-links' : 'editor-unlink', size: 'small', onClick: () => setLinked(!linked) }))));
  }

  function Color({ label, k, ctx }) {
    const v = ctx.cur[k] || '';
    const shown = (PAL.find(p => p.slug === v) || {}).color || v;
    return el(Field, { label, set: !!v }, el(Dropdown, {
      popoverProps: { placement: 'left-start' },
      renderToggle: ({ isOpen, onToggle }) => el(Button, { className: 'igs-color', onClick: onToggle, 'aria-expanded': isOpen }, el(ColorIndicator, { colorValue: shown || 'transparent' }), el('span', {}, v ? ((PAL.find(p => p.slug === v) || {}).name || v) : (inherited(ctx.style, ctx.bp, s => s[k]) || 'Default'))),
      renderContent: () => el('div', { className: 'igs-color-pop' }, el(ColorPalette, { colors: PAL, value: shown, enableAlpha: true, onChange: c => { const p = PAL.find(x => (x.color || '').toLowerCase() === String(c || '').toLowerCase()); ctx.setProp(k, c ? (p ? p.slug : c) : ''); } }))
    }));
  }

  function Image({ label, k, ctx }) {
    const v = ctx.cur[k] || '';
    return el(Field, { label, set: !!v }, el(MediaUploadCheck, {}, el(MediaUpload, {
      allowedTypes: ['image'], onSelect: m => ctx.setProp(k, m.url),
      render: ({ open }) => el('div', { className: 'igs-image' }, v ? el('img', { src: v, alt: '', onClick: open }) : null,
        el(Button, { variant: 'secondary', size: 'small', onClick: open }, v ? 'Replace' : 'Choose image'),
        v ? el(Button, { variant: 'link', isDestructive: true, size: 'small', onClick: () => ctx.setProp(k, '') }, 'Remove') : null)
    })));
  }

  const Group = ({ title, ctx, keys, children, open }) => {
    const count = keys.filter(k => { const v = ctx.cur[k]; return v && (typeof v !== 'object' || !isEmpty(v)); }).length;
    return el(PanelBody, { title: title + (count ? ` · ${count}` : ''), initialOpen: !!open, className: 'igs-panel' }, children);
  };

  /* ---------------- the Style tab ---------------- */
  function StylePanels({ attributes, setAttributes, name }) {
    const style = attributes.igStyle || {};
    const [bp, setBp] = useState('d');
    const [state, setState] = useState('normal');
    const key = state === 'hover' ? 'h' : bp;
    const cur = style[key] || {};
    const setProp = (k, v) => {
      const next = { ...style, [key]: { ...cur, [k]: v } };
      if (v === '' || v === undefined) delete next[key][k];
      if (isEmpty(next[key])) delete next[key];
      setAttributes({ igStyle: isEmpty(next) && !next.css ? undefined : next });
    };
    const ctx = { style, bp: key, cur, setProp };
    const used = ['d', 't', 'm', 'h'].filter(b => !isEmpty(style[b]));
    const opts = list => list;
    return el(Fragment, {},
      el(PanelBody, { title: 'Imagient Style', initialOpen: true, className: 'igs-head' },
        el('div', { className: 'igs-switch' },
          STATES.map(s => el(Tooltip, { key: s.k, text: s.label }, el(Button, { icon: s.icon, isPressed: state === 'normal' && bp === s.k, className: used.includes(s.k) ? 'has-values' : '', onClick: () => { setBp(s.k); setState('normal'); } }))),
          el('span', { className: 'igs-sep' }),
          el(Button, { isPressed: state === 'hover', className: used.includes('h') ? 'has-values' : '', onClick: () => setState(state === 'hover' ? 'normal' : 'hover') }, 'Hover')),
        el('p', { className: 'igs-note' }, state === 'hover' ? 'Hover: values applied when the pointer is over the block.' : bp === 'd' ? 'Desktop values apply on every screen unless Tablet or Mobile change them.' : (bp === 't' ? 'Tablet (≤ 1024 px). Empty fields inherit Desktop (shown in grey).' : 'Mobile (≤ 640 px). Empty fields inherit Tablet / Desktop.')),
        !isEmpty(style) || style.css ? el(Button, { variant: 'link', isDestructive: true, onClick: () => setAttributes({ igStyle: undefined }) }, 'Reset all styles') : null),

      el(Group, { title: 'Layout', ctx, keys: ['display', 'flexDirection', 'justifyContent', 'alignItems', 'flexWrap', 'gap', 'gridTemplateColumns', 'alignSelf', 'order'] },
        el(Select, { label: 'Display', k: 'display', ctx, options: ['block', 'flex', 'grid', 'inline-block', 'inline-flex', 'inline', 'none'] }),
        el(Select, { label: 'Direction', k: 'flexDirection', ctx, options: ['row', 'column', 'row-reverse', 'column-reverse'] }),
        el(Select, { label: 'Justify', k: 'justifyContent', ctx, options: ['flex-start', 'center', 'flex-end', 'space-between', 'space-around', 'space-evenly'] }),
        el(Select, { label: 'Align items', k: 'alignItems', ctx, options: ['stretch', 'flex-start', 'center', 'flex-end', 'baseline'] }),
        el(Select, { label: 'Wrap', k: 'flexWrap', ctx, options: ['nowrap', 'wrap', 'wrap-reverse'] }),
        el(Value, { label: 'Gap', k: 'gap', ctx, placeholder: 'e.g. 24px' }),
        el(Value, { label: 'Grid columns', k: 'gridTemplateColumns', ctx, placeholder: 'e.g. repeat(3, 1fr)' }),
        el(Value, { label: 'Grid column (child)', k: 'gridColumn', ctx, placeholder: 'e.g. span 2' }),
        el(Select, { label: 'Align self', k: 'alignSelf', ctx, options: ['auto', 'stretch', 'flex-start', 'center', 'flex-end'] }),
        el(Value, { label: 'Order', k: 'order', ctx, placeholder: '0' }),
        el(Value, { label: 'Flex (child)', k: 'flex', ctx, placeholder: 'e.g. 1 1 0' })),

      el(Group, { title: 'Spacing', ctx, keys: ['margin', 'padding'], open: true },
        el(Sides, { label: 'Margin (space outside)', k: 'margin', ctx }),
        el(Sides, { label: 'Padding (space inside)', k: 'padding', ctx })),

      el(Group, { title: 'Size', ctx, keys: ['width', 'height', 'minWidth', 'maxWidth', 'minHeight', 'maxHeight', 'overflow', 'aspectRatio', 'objectFit'] },
        el(Value, { label: 'Width', k: 'width', ctx, placeholder: 'auto, 100%, 480px' }),
        el(Value, { label: 'Height', k: 'height', ctx, placeholder: 'auto, 100vh' }),
        el(Value, { label: 'Min width', k: 'minWidth', ctx }),
        el(Value, { label: 'Max width', k: 'maxWidth', ctx, placeholder: 'e.g. 22ch, 960px' }),
        el(Value, { label: 'Min height', k: 'minHeight', ctx }),
        el(Value, { label: 'Max height', k: 'maxHeight', ctx }),
        el(Select, { label: 'Overflow', k: 'overflow', ctx, options: ['visible', 'hidden', 'auto', 'clip'] }),
        el(Value, { label: 'Aspect ratio', k: 'aspectRatio', ctx, placeholder: 'e.g. 16 / 9' }),
        el(Select, { label: 'Object fit (images)', k: 'objectFit', ctx, options: ['cover', 'contain', 'fill', 'none'] })),

      el(Group, { title: 'Position', ctx, keys: ['position', 'inset', 'zIndex'] },
        el(Select, { label: 'Position', k: 'position', ctx, options: ['static', 'relative', 'absolute', 'fixed', 'sticky'] }),
        el(Sides, { label: 'Offsets', k: 'inset', ctx }),
        el(Value, { label: 'Z-index', k: 'zIndex', ctx })),

      el(Group, { title: 'Typography', ctx, keys: ['fontFamily', 'fontSize', 'fontWeight', 'lineHeight', 'letterSpacing', 'textTransform', 'textDecoration', 'fontStyle', 'textAlign', 'color'] },
        el(Select, { label: 'Font', k: 'fontFamily', ctx, options: [{ value: 'var(--wp--preset--font-family--area)', label: 'Area Normal' }, { value: 'system-ui, sans-serif', label: 'System' }, { value: 'Georgia, serif', label: 'Serif' }, { value: 'ui-monospace, monospace', label: 'Monospace' }] }),
        el(Value, { label: 'Size', k: 'fontSize', ctx, placeholder: 'e.g. 48px, 3rem, clamp(28px, 3.3vw, 72px)' }),
        el(Select, { label: 'Weight', k: 'fontWeight', ctx, options: [{ value: '400', label: 'Regular (400)' }, { value: '700', label: 'Bold (700)' }, '300', '500', '600', '800'] }),
        el(Value, { label: 'Line height', k: 'lineHeight', ctx, placeholder: 'e.g. 1.15' }),
        el(Value, { label: 'Letter spacing', k: 'letterSpacing', ctx, placeholder: 'e.g. -0.02em' }),
        el(Select, { label: 'Transform', k: 'textTransform', ctx, options: ['none', 'uppercase', 'lowercase', 'capitalize'] }),
        el(Select, { label: 'Decoration', k: 'textDecoration', ctx, options: ['none', 'underline', 'line-through'] }),
        el(Select, { label: 'Style', k: 'fontStyle', ctx, options: ['normal', 'italic'] }),
        el(Select, { label: 'Align', k: 'textAlign', ctx, options: ['left', 'center', 'right', 'justify'] }),
        el(Color, { label: 'Text colour', k: 'color', ctx })),

      el(Group, { title: 'Background', ctx, keys: ['backgroundColor', 'backgroundImage', 'backgroundGradient', 'backgroundSize', 'backgroundPosition', 'backgroundRepeat'] },
        el(Color, { label: 'Colour', k: 'backgroundColor', ctx }),
        el(Image, { label: 'Image', k: 'backgroundImage', ctx }),
        el(Value, { label: 'Gradient', k: 'backgroundGradient', ctx, placeholder: 'linear-gradient(158deg, #145fad, #2aa8da)' }),
        el(Select, { label: 'Size', k: 'backgroundSize', ctx, options: ['cover', 'contain', 'auto'] }),
        el(Value, { label: 'Position', k: 'backgroundPosition', ctx, placeholder: 'center, 50% 30%' }),
        el(Select, { label: 'Repeat', k: 'backgroundRepeat', ctx, options: ['no-repeat', 'repeat', 'repeat-x', 'repeat-y'] }),
        el(Select, { label: 'Attachment', k: 'backgroundAttachment', ctx, options: ['scroll', 'fixed'] })),

      el(Group, { title: 'Border', ctx, keys: ['borderStyle', 'borderWidth', 'borderColor', 'borderRadius'] },
        el(Select, { label: 'Style', k: 'borderStyle', ctx, options: ['none', 'solid', 'dashed', 'dotted', 'double'] }),
        el(Sides, { label: 'Width', k: 'borderWidth', ctx }),
        el(Color, { label: 'Colour', k: 'borderColor', ctx }),
        el(Sides, { label: 'Radius', k: 'borderRadius', ctx, corners: true })),

      el(Group, { title: 'Effects', ctx, keys: ['opacity', 'boxShadow', 'transform', 'transition', 'filter', 'cursor', 'mixBlendMode'] },
        el(Value, { label: 'Opacity', k: 'opacity', ctx, placeholder: '0 – 1' }),
        el(Value, { label: 'Box shadow', k: 'boxShadow', ctx, placeholder: '0 10px 40px rgba(20,95,173,.15)' }),
        el(Value, { label: 'Transform', k: 'transform', ctx, placeholder: 'translateY(-4px) scale(1.02)' }),
        el(Value, { label: 'Transition', k: 'transition', ctx, placeholder: 'all .4s ease' }),
        el(Value, { label: 'Filter', k: 'filter', ctx, placeholder: 'grayscale(1), blur(4px)' }),
        el(Value, { label: 'Backdrop filter', k: 'backdropFilter', ctx, placeholder: 'blur(10px)' }),
        el(Select, { label: 'Cursor', k: 'cursor', ctx, options: ['pointer', 'default', 'grab'] }),
        el(Select, { label: 'Blend mode', k: 'mixBlendMode', ctx, options: ['normal', 'multiply', 'screen', 'overlay'] })),

      el(PanelBody, { title: 'Custom CSS' + (style.css ? ' · on' : ''), initialOpen: false, className: 'igs-panel' },
        el(TextareaControl, { value: style.css || '', rows: 6, className: 'igs-css', help: 'Use "selector" for this block, e.g. selector h2 { color: #145fad; } or selector:hover { … }',
          onChange: v => { const n = { ...style, css: v }; if (!v) delete n.css; setAttributes({ igStyle: isEmpty(n) && !n.css ? undefined : n }); } })));
  }

  /* ---------------- register on every block ---------------- */
  addFilter('blocks.registerBlockType', 'imagient/style-attribute', settings => {
    settings.attributes = Object.assign({}, settings.attributes, { igStyle: { type: 'object' } });
    return settings;
  });
  const hasStylesGroup = !!(wp.blockEditor.InspectorControls && wp.blockEditor.InspectorControls.Slot);
  addFilter('editor.BlockEdit', 'imagient/style-panel', createHigherOrderComponent(BlockEdit => props => {
    const style = props.attributes.igStyle;
    const preview = style ? css(style, `#block-${props.clientId}#block-${props.clientId}`) : '';
    return el(Fragment, {},
      el(BlockEdit, props),
      preview ? el('style', {}, preview) : null,
      props.isSelected ? el(InspectorControls, hasStylesGroup ? { group: 'styles' } : {}, el(StylePanels, props)) : null);
  }, 'withImagientStyle'));
})(window.wp);
