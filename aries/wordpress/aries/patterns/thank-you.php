<?php
/**
 * Title: Thank you page
 * Slug: aries/thank-you
 * Categories: aries
 * Keywords: thank you, message sent, contact, form
 * Description: The page a visitor lands on after sending the contact form: one blue screen with the Group ribbon, the thank-you message and a link back to the home page. Use it with the "Company page" template (the header sits over it).
 * Viewport Width: 1440
 * Block Types: core/post-content
 */
?>
<!-- wp:imagient/section {"background":"group","tone":"dark","minHeight":"screen","className":"ig-thanks","spacing":{"d":{"pt":"0","pb":"8"},"t":{},"m":{}}} -->
<!-- wp:imagient/ribbon {"shape":"group","size":108,"x":-4,"y":64} /-->

<!-- wp:heading {"level":1,"className":"ig-statement","igAnim":"split"} -->
<h1 class="wp-block-heading ig-statement">Thank you! Your message has been sent successfully.</h1>
<!-- /wp:heading -->

<!-- wp:paragraph {"className":"ig-thanks__lead","igAnim":"fade-up"} -->
<p class="ig-thanks__lead">A representative of Aries Group will contact you shortly for further details about your inquiry.</p>
<!-- /wp:paragraph -->

<!-- wp:buttons {"igAnim":"fade-up"} -->
<div class="wp-block-buttons"><!-- wp:button {"className":"is-style-aries-link"} -->
<div class="wp-block-button is-style-aries-link"><a class="wp-block-button__link wp-element-button" href="/">Back to the home page</a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons -->
<!-- /wp:imagient/section -->
