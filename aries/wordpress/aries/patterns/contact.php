<?php
/**
 * Title: Contact page
 * Slug: aries/contact
 * Categories: aries
 * Keywords: contact, form, phone, email, address
 * Description: The contact page from the design, as the Aries Group page: the page hero (the home page's frame, closing with the scroll) with the phone numbers, email and address in its scene, the contact form on blue and a closing photo. Use it with the "Company page" template. The form is Contact Form 7's "Contact form" (Contact → Contact Forms): its fields, recipients and messages are set there; its hidden field "redirect" is the page shown once it is sent.
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

<!-- wp:shortcode -->
[contact-form-7 title="Contact form"]
<!-- /wp:shortcode -->
<!-- /wp:imagient/section -->

<!-- wp:imagient/cover {"imageUrl":"<?php echo esc_url( get_theme_file_uri( 'assets/images/about-group.webp' ) ); ?>","alt":"A bulk carrier moored under a gantry crane in the port of Constanta","from":"group","to":"group","shade":"tint"} /-->
