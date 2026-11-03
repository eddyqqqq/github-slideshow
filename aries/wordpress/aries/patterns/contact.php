<?php
/**
 * Title: Contact page
 * Slug: aries/contact
 * Categories: aries
 * Keywords: contact, form, phone, email, address
 * Description: The contact page from the design, as the Aries Group page: the page hero (the home page's frame, closing with the scroll) with the phone numbers, email and address in its scene, the contact form on blue and a closing photo. Use it with the "Company page" template. The form's recipients and what happens after sending are in the Form block's sidebar.
 * Viewport Width: 1440
 * Block Types: core/post-content
 */
?>
<!-- wp:imagient/page-hero {"title":"Contact","frame":"group","color":"group","imageUrl":"<?php echo esc_url( get_theme_file_uri( 'assets/images/interlink-wagons.webp' ) ); ?>","focusX":50,"focusY":50,"scene":"1","anchor":"contact-details"} -->
<!-- wp:group {"className":"ig-contact-lines","layout":{"type":"default"}} -->
<div class="wp-block-group ig-contact-lines"><!-- wp:paragraph -->
<p><a href="tel:+40241550871">+ 40 241 550 871</a></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p>+ 40 241 550 898 (fax)</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p><a href="mailto:office@aries-group.ro">office@aries-group.ro</a></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p>175 Mamaia Blvd.,<br>900565 Constanta,<br>Romania</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->
<!-- /wp:imagient/page-hero -->

<!-- wp:imagient/section {"background":"group","tone":"dark","className":"ig-contact-form","anchor":"contact-form","spacing":{"d":{"pt":"8","pb":"9"},"t":{},"m":{}}} -->
<!-- wp:heading {"className":"ig-contact-form__title","igAnim":"split"} -->
<h2 class="wp-block-heading ig-contact-form__title">Contact form</h2>
<!-- /wp:heading -->

<!-- wp:imagient/form {"formKey":"contact01","formName":"Contact form","after":"redirect","redirect":"/thank-you/","subject":"New message from the website – {name}"} -->
<!-- wp:imagient/field {"label":"Name","required":true,"width":"50","placeholder":"Name","hideLabel":true,"autocomplete":"name"} /-->

<!-- wp:imagient/field {"label":"Company","width":"50","placeholder":"Company","hideLabel":true,"autocomplete":"organization"} /-->

<!-- wp:imagient/field {"type":"tel","label":"Phone","width":"50","placeholder":"Phone","hideLabel":true} /-->

<!-- wp:imagient/field {"type":"email","label":"Email","required":true,"width":"50","placeholder":"Email","hideLabel":true} /-->

<!-- wp:imagient/field {"type":"textarea","label":"Message","required":true,"placeholder":"Message","hideLabel":true,"rows":4} /-->

<!-- wp:imagient/field {"type":"consent","label":"I agree that my personal data will be collected and processed for the handling of my application in accordance with the <a href=\"/privacy-policy/\">Privacy policy</a>.","required":true,"name":"consent"} /-->

<!-- wp:imagient/form-submit {"text":"Send","sending":"Sending…"} /-->
<!-- /wp:imagient/form -->
<!-- /wp:imagient/section -->

<!-- wp:imagient/cover {"imageUrl":"<?php echo esc_url( get_theme_file_uri( 'assets/images/shipping-agency-port.webp' ) ); ?>","alt":"A bulk carrier at the grain terminal in the port of Constanta","from":"group","to":"group","shade":"tint"} /-->
