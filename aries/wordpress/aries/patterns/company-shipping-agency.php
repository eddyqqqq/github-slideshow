<?php
/**
 * Title: Company page: Aries Shipping Agency
 * Slug: aries/company-shipping-agency
 * Categories: aries
 * Keywords: company, shipping agency, page
 * Description: The whole Aries Shipping Agency page from the design: page hero, the cover with the photo, the figures with the ribbon, the services list and the closing photo. Use it with the "Company page" template; for Interlink or Logistics change the texts, the photos and the colour.
 * Viewport Width: 1440
 * Block Types: core/post-content
 */
?>
<!-- wp:imagient/page-hero /-->

<!-- wp:imagient/cover {"anchor":"about"} -->
<!-- wp:heading {"className":"ig-page-statement","igAnim":"split"} -->
<h2 class="wp-block-heading ig-page-statement">Maritime and river agency providing comprehensive agency services in all Romanian Black Sea and Danube ports.</h2>
<!-- /wp:heading -->

<!-- wp:buttons {"igAnim":"fade-up"} -->
<div class="wp-block-buttons"><!-- wp:button {"className":"is-style-aries-link"} -->
<div class="wp-block-button is-style-aries-link"><a class="wp-block-button__link wp-element-button" href="#contact">Contact us</a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons -->
<!-- /wp:imagient/cover -->

<!-- wp:imagient/section {"background":"agency","tone":"dark","className":"ig-page-figures","spacing":{"d":{"pt":"8","pb":"8"},"t":{},"m":{}}} -->
<!-- wp:imagient/ribbon {"shape":"agency","size":112,"x":-6,"y":40,"pan":true} /-->

<!-- wp:heading {"className":"ig-page-statement","igAnim":"split"} -->
<h2 class="wp-block-heading ig-page-statement">Handling over 600 maritime port calls and over 800 inland waterway port calls annually.</h2>
<!-- /wp:heading -->

<!-- wp:group {"className":"ig-page-notes","layout":{"type":"default"},"igAnim":"fade-up"} -->
<div class="wp-block-group ig-page-notes"><!-- wp:paragraph -->
<p>Our maritime division specializes in handling a wide variety of vessel types and cargoes, including agricultural commodities, fertilizers, petroleum/liquid products, steel products, project cargo, Ro-Ro vessels, cruise ships and military vessels.</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p>Beyond traditional ship agency services, we provide cargo operation supervision, port captain services, customs formalities and operational consultancy, ensuring the highest standards of efficiency and compliance.</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p>Our river division offers dedicated agency services for river vessels, barges and pushers operating throughout the Romanian Danube sector.</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p>Services include channel transit formalities, pilotage and towage coordination, customs transit procedures and brokerage for cargo transported via inland waterways.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->
<!-- /wp:imagient/section -->

<!-- wp:imagient/section {"className":"ig-page-services","spacing":{"d":{"pt":"8","pb":"8"},"t":{},"m":{}}} -->
<!-- wp:imagient/services /-->
<!-- /wp:imagient/section -->

<!-- wp:imagient/cover {"imageUrl":"<?php echo esc_url( get_theme_file_uri( 'assets/images/shipping-agency-port.webp' ) ); ?>","alt":"The bulk carrier Lausanne moored at the grain terminal","from":"agency","to":"group","shade":"tint"} /-->
