=== UMEX Hardening ===
Requires at least: 6.0
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later

Rezolvă punctele din raportul de securitate fără să modifice fișierele WordPress.

== Ce face ==

* Blochează xmlrpc.php (403) și pingback-urile.
* Ascunde /wp-json/wp/v2/users și ?author=N pentru vizitatorii nelogați.
* Opțional: acceptă wp-cron.php doar cu cheie secretă (cron-ul intern WordPress primește cheia automat).
* Header-e: HSTS, X-Content-Type-Options, X-Frame-Options, Referrer-Policy, Permissions-Policy, CSP minimal.
* Opțional: scrie aceleași reguli în .htaccess (Apache/LiteSpeed), pentru fișiere statice și pagini din cache.
* Opțional: CSP completă în mod Report-Only, cu tabel de rapoarte în admin.
* Pagină Setări → UMEX Hardening, cu buton de verificare.

== Filtre ==

* umex_hardening_allow_xmlrpc (bool) – întoarce true ca să lași XML-RPC deschis.
* umex_hardening_headers (array) – modifică header-ele trimise.
