/* Imagient Forms — the editor: the Form block (its settings in the sidebar, its fields inside it), the Form field
   block (one block, a type for each kind of field, each listed in the inserter) and the Submit button. */
(function (wp) {
  const el = wp.element.createElement, Fragment = wp.element.Fragment, { useEffect } = wp.element;
  const { registerBlockType, registerBlockVariation } = wp.blocks;
  const { InnerBlocks, InspectorControls, RichText, useBlockProps, URLInput } = wp.blockEditor;
  const { PanelBody, TextControl, TextareaControl, ToggleControl, SelectControl, RadioControl, Notice } = wp.components;
  const { useSelect, select } = wp.data;
  const TYPES = (window.IGF && window.IGF.types) || {};
  const ADMIN = (window.IGF && window.IGF.admin) || '';

  const newKey = () => 'f' + Math.random().toString(36).slice(2, 10);
  // a field's name as the server makes it: the one set, or its label's words
  const slug = s => (s || '').replace(/<[^>]*>/g, '').normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase().replace(/[^a-z0-9]+/g, '_').replace(/^_+|_+$/g, '').slice(0, 40);
  const nameOf = a => slug(a.name) || slug(a.label) || 'field';
  const fieldsIn = blocks => blocks.flatMap(b => b.name === 'imagient/field' ? [b] : fieldsIn(b.innerBlocks || []));

  /* ---------------- Form ---------------- */
  registerBlockType('imagient/form', {
    icon: 'feedback',
    edit({ attributes: a, setAttributes, clientId }) {
      // its key: made once; a copy of a form gets its own
      useEffect(() => {
        const be = select('core/block-editor'), all = be.getClientIdsWithDescendants();
        const same = all.filter(id => be.getBlockName(id) === 'imagient/form' && be.getBlockAttributes(id).formKey === a.formKey);
        if (!a.formKey || same.indexOf(clientId) > 0) setAttributes({ formKey: newKey() });   // the later copy takes a new key
      }, []);
      const fields = useSelect(s => fieldsIn(s('core/block-editor').getBlocks(clientId)), [clientId]);
      const names = fields.filter(f => f.attributes.type !== 'hidden').map(f => nameOf(f.attributes));
      const emails = fields.filter(f => f.attributes.type === 'email').map(f => nameOf(f.attributes));
      const ph = '{form} {site} {page} {date}' + (names.length ? ' · ' + names.map(n => '{' + n + '}').join(' ') : '');
      const props = useBlockProps({ className: 'ig-form ig-form-edit' });
      return el(Fragment, {},
        el(InspectorControls, {},
          el(PanelBody, { title: 'Form', initialOpen: true },
            el(TextControl, { label: 'Form name', help: 'Shown in the emails and under Forms → Messages.', value: a.formName, onChange: v => setAttributes({ formName: v }) })),
          el(PanelBody, { title: 'Email', initialOpen: true },
            el(TextControl, { label: 'Send to', type: 'text', placeholder: ADMIN, help: 'One or more addresses, commas between them. Empty: the default in Forms → Settings, or ' + ADMIN + '.', value: a.to, onChange: v => setAttributes({ to: v }) }),
            el(TextControl, { label: 'Cc', value: a.cc, onChange: v => setAttributes({ cc: v }) }),
            el(TextControl, { label: 'Bcc', value: a.bcc, onChange: v => setAttributes({ bcc: v }) }),
            el(TextControl, { label: 'Subject', help: 'You can use: ' + ph, value: a.subject, onChange: v => setAttributes({ subject: v }) }),
            el(TextControl, { label: 'From name', placeholder: 'The site\'s name', value: a.fromName, onChange: v => setAttributes({ fromName: v }) }),
            el(SelectControl, { label: 'Reply-to', help: 'Answering the email writes to the visitor.', value: a.replyTo, options: [{ value: 'auto', label: 'The first email field' }].concat(emails.map(n => ({ value: n, label: 'Field: ' + n }))), onChange: v => setAttributes({ replyTo: v }) })),
          el(PanelBody, { title: 'After sending', initialOpen: true },
            el(RadioControl, { selected: a.after, options: [{ value: 'message', label: 'Show a message' }, { value: 'redirect', label: 'Go to a page' }], onChange: v => setAttributes({ after: v }) }),
            a.after === 'redirect'
              ? el('div', { className: 'igf-url' }, el('p', { style: { margin: '0 0 6px', fontSize: '11px', textTransform: 'uppercase', fontWeight: 500 } }, 'Page'),
                  el(URLInput, { value: a.redirect, onChange: v => setAttributes({ redirect: v }), placeholder: 'Search a page or paste an address' }),
                  el('p', { className: 'components-base-control__help', style: { marginTop: '6px' } }, 'A page of this site (e.g. Thank you) or any address.'))
              : el(TextareaControl, { label: 'Message', value: a.message, onChange: v => setAttributes({ message: v }) })),
          el(PanelBody, { title: 'Messages', initialOpen: false },
            el(TextControl, { label: 'Required field left empty', value: a.msgRequired, onChange: v => setAttributes({ msgRequired: v }) }),
            el(TextControl, { label: 'Email not valid', value: a.msgEmail, onChange: v => setAttributes({ msgEmail: v }) }),
            el(TextControl, { label: 'Other field not valid', value: a.msgInvalid, onChange: v => setAttributes({ msgInvalid: v }) }),
            el(TextControl, { label: 'Could not send', value: a.msgError, onChange: v => setAttributes({ msgError: v }) }),
            a.after === 'redirect' ? el(TextareaControl, { label: 'Message (when the page cannot be reached)', value: a.message, onChange: v => setAttributes({ message: v }) }) : null),
          el(PanelBody, { title: 'Confirmation to the visitor', initialOpen: false },
            el(ToggleControl, { label: 'Send the visitor a confirmation email', checked: !!a.confirm, onChange: v => setAttributes({ confirm: v }) }),
            a.confirm ? el(Fragment, {},
              emails.length ? null : el(Notice, { status: 'warning', isDismissible: false }, 'Add an Email field: the confirmation goes to it.'),
              el(TextControl, { label: 'Subject', value: a.confirmSubject, onChange: v => setAttributes({ confirmSubject: v }) }),
              el(TextareaControl, { label: 'Text', rows: 7, help: 'You can use: ' + ph + ' and {all_fields} (every answer, as a table).', value: a.confirmBody, onChange: v => setAttributes({ confirmBody: v }) })) : null),
          el(PanelBody, { title: 'Messages received', initialOpen: false },
            el(ToggleControl, { label: 'Keep every message under Forms', help: 'Kept even when the email cannot go out; exported as CSV from there.', checked: a.store !== false, onChange: v => setAttributes({ store: v }) }))),
        el('div', props,
          el('span', { className: 'ig-form-edit__badge' }, 'Form · ' + (a.formName || '') + (a.after === 'redirect' && a.redirect ? ' → ' + a.redirect : '')),
          el(InnerBlocks, {
            template: [
              ['imagient/field', { type: 'text', label: 'Name', required: true, width: '50', autocomplete: 'name' }],
              ['imagient/field', { type: 'email', label: 'Email', required: true, width: '50' }],
              ['imagient/field', { type: 'tel', label: 'Phone', width: '50' }],
              ['imagient/field', { type: 'text', label: 'Company', width: '50', autocomplete: 'organization' }],
              ['imagient/field', { type: 'textarea', label: 'Message', required: true }],
              ['imagient/field', { type: 'consent', label: 'I agree to the processing of my personal data.', required: true }],
              ['imagient/form-submit', {}]
            ],
            templateLock: false
          })));
    },
    save: () => el(InnerBlocks.Content)
  });

  /* ---------------- Form field ---------------- */
  const CHOICE = ['select', 'radio', 'checkbox'];
  const preview = a => {
    const t = a.type, opts = (a.options || '').split(/\n/).map(s => s.trim()).filter(Boolean).map(s => (s.split('|')[1] || s.split('|')[0]).trim());
    if (t === 'textarea') return el('textarea', { className: 'ig-field__input', rows: a.rows || 5, placeholder: a.placeholder, readOnly: true, tabIndex: -1 });
    if (t === 'select') return el('select', { className: 'ig-field__input', tabIndex: -1, value: '', onChange: () => {} }, el('option', { value: '' }, a.placeholder || '—'), opts.map((o, i) => el('option', { key: i }, o)));
    if (t === 'radio' || t === 'checkbox') return el('div', { className: 'ig-field__choices' }, (opts.length ? opts : ['Option']).map((o, i) => el('label', { key: i, className: 'ig-field__choice' }, el('input', { type: t, tabIndex: -1, readOnly: true }), el('span', {}, o))));
    const html = { text: 'text', email: 'email', tel: 'tel', number: 'number', date: 'date', url: 'url' }[t] || 'text';
    return el('input', { className: 'ig-field__input', type: html, placeholder: a.placeholder, value: a.defaultValue || '', readOnly: true, tabIndex: -1 });
  };
  registerBlockType('imagient/field', {
    icon: 'editor-textcolor',
    edit({ attributes: a, setAttributes }) {
      const t = a.type;
      const props = useBlockProps({ className: 'ig-field ig-field--' + t + ' is-w-' + (t === 'hidden' ? '100' : a.width) + (a.required ? ' is-required' : '') + (a.hideLabel ? ' has-hidden-label' : '') });
      const star = a.required ? el('span', { className: 'ig-field__req' }, '*') : null;
      const label = el(RichText, { tagName: 'span', value: a.label, allowedFormats: ['core/bold', 'core/italic', 'core/link'], placeholder: t === 'consent' ? 'I agree to …' : 'Label', onChange: v => setAttributes({ label: v }) });
      return el(Fragment, {},
        el(InspectorControls, {},
          el(PanelBody, { title: 'Field', initialOpen: true },
            el(SelectControl, { label: 'Type', value: t, options: Object.keys(TYPES).map(k => ({ value: k, label: TYPES[k] })), onChange: v => setAttributes({ type: v }) }),
            t !== 'hidden' ? el(ToggleControl, { label: 'Required', checked: !!a.required, onChange: v => setAttributes({ required: v }) }) : null,
            t !== 'hidden' ? el(SelectControl, { label: 'Width', help: 'Fields side by side on wide screens; one under the other on phones.', value: a.width, options: [{ value: '100', label: 'Full' }, { value: '75', label: 'Three quarters' }, { value: '66', label: 'Two thirds' }, { value: '50', label: 'Half' }, { value: '33', label: 'One third' }, { value: '25', label: 'One quarter' }], onChange: v => setAttributes({ width: v }) }) : null,
            !CHOICE.includes(t) && !['consent', 'hidden', 'date'].includes(t) || t === 'select' ? el(TextControl, { label: t === 'select' ? 'First line (nothing chosen)' : 'Placeholder', value: a.placeholder, onChange: v => setAttributes({ placeholder: v }) }) : null,
            CHOICE.includes(t) ? el(TextareaControl, { label: 'Options', help: 'One per line. "value|Label" keeps the value sent apart from the label shown.', rows: 6, value: a.options, onChange: v => setAttributes({ options: v }) }) : null,
            t === 'textarea' ? el(TextControl, { label: 'Lines', type: 'number', min: 2, max: 20, value: a.rows, onChange: v => setAttributes({ rows: parseInt(v, 10) || 5 }) }) : null,
            t !== 'consent' ? el(TextControl, { label: t === 'hidden' ? 'Value' : 'Default value', help: t === 'checkbox' ? 'Values ticked by default, commas between them.' : null, value: a.defaultValue, onChange: v => setAttributes({ defaultValue: v }) }) : null,
            t !== 'hidden' ? el(TextControl, { label: 'Help text', value: a.help, onChange: v => setAttributes({ help: v }) }) : null,
            t !== 'hidden' && t !== 'consent' ? el(ToggleControl, { label: 'Hide the label (kept for screen readers)', checked: !!a.hideLabel, onChange: v => setAttributes({ hideLabel: v }) }) : null),
          el(PanelBody, { title: 'Advanced', initialOpen: false },
            el(TextControl, { label: 'Field name', help: 'Its key in the message and in the email placeholders, e.g. {' + nameOf(a) + '}. Empty: made from the label.', placeholder: slug(a.label) || 'field', value: a.name, onChange: v => setAttributes({ name: slug(v) || '' }) }),
            ['text', 'tel', 'email', 'url'].includes(t) ? el(TextControl, { label: 'Autocomplete', help: 'What browsers may fill in: name, organization, street-address …', value: a.autocomplete, onChange: v => setAttributes({ autocomplete: v }) }) : null)),
        el('div', props,
          t === 'hidden' ? el('div', { className: 'ig-form-edit__hidden' }, 'Hidden field · ' + nameOf(a) + (a.defaultValue ? ' = ' + a.defaultValue : '')) :
          t === 'consent' ? el('div', { className: 'ig-field__choice ig-field__consent' }, el('input', { type: 'checkbox', tabIndex: -1, readOnly: true }), el('span', {}, label, star)) :
          el(Fragment, {},
            el('div', { className: 'ig-field__label' }, label, star),
            preview(a),
            a.help ? el('p', { className: 'ig-field__help' }, a.help) : null)));
    },
    save: () => null
  });
  // each type in the inserter, as Elementor lists them
  const V = [
    ['text', 'Text field', 'editor-textcolor', { label: 'Name' }], ['email', 'Email field', 'email', { label: 'Email' }],
    ['tel', 'Phone field', 'phone', { label: 'Phone' }], ['textarea', 'Message field', 'text', { label: 'Message' }],
    ['select', 'Dropdown', 'menu-alt', { label: 'Subject', options: 'General enquiry\nShipping agency\nRail freight\nLogistics' }],
    ['radio', 'Radio buttons', 'marker', { label: 'Choose one', options: 'Option 1\nOption 2' }],
    ['checkbox', 'Checkboxes', 'yes-alt', { label: 'Choose', options: 'Option 1\nOption 2' }],
    ['consent', 'Consent (GDPR)', 'shield', { label: 'I agree to the processing of my personal data.', required: true }],
    ['number', 'Number field', 'calculator', { label: 'Number' }], ['date', 'Date field', 'calendar-alt', { label: 'Date' }],
    ['url', 'Website field', 'admin-links', { label: 'Website' }], ['hidden', 'Hidden field', 'hidden', { label: 'Source', name: 'source' }]
  ];
  V.forEach(([type, title, icon, extra], i) => registerBlockVariation('imagient/field', {
    name: type, title, icon, isDefault: i === 0, scope: ['inserter', 'transform'],
    attributes: Object.assign({ type }, extra), isActive: (a, v) => a.type === v.type
  }));

  /* ---------------- Submit button ---------------- */
  registerBlockType('imagient/form-submit', {
    icon: 'button',
    edit({ attributes: a, setAttributes }) {
      const props = useBlockProps({ className: 'ig-form__submit' + (a.width === 'full' ? ' is-full' : '') });
      return el(Fragment, {},
        el(InspectorControls, {},
          el(PanelBody, { title: 'Button', initialOpen: true },
            el(TextControl, { label: 'Text while sending', value: a.sending, onChange: v => setAttributes({ sending: v }) }),
            el(SelectControl, { label: 'Width', value: a.width, options: [{ value: 'auto', label: 'As its text' }, { value: 'full', label: 'Full width' }], onChange: v => setAttributes({ width: v }) }))),
        el('div', props, el('span', { className: 'ig-form__button', role: 'presentation' },
          el(RichText, { tagName: 'span', value: a.text, allowedFormats: [], placeholder: 'Send message', onChange: v => setAttributes({ text: v.replace(/<[^>]*>/g, '') }) }))));
    },
    save: () => null
  });
})(window.wp);
