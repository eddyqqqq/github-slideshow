=== Pavva Products ===
Creat de: imagient
Pentru: Pavva
Versiune: 5.1.0
Necesită: WordPress 6.0+, PHP 7.4+, WooCommerce
Lucrează împreună cu: Pavva Widgets 4.0.1+ (afișarea pe site)

Câmpurile din pagina de editare a produsului WooCommerce:
- Descriere scurtă, Descriere sinteza
- Caracteristici
- Culori și finisaje (cu numărul de coloane)
- Dimensiuni și specificații
- Variante de amenajare
- Fișe tehnice (PDF)
- Declarații de performanță (PDF)
- Descriere produs

Fiecare element are bifa „vizibil” și se poate reordona prin tragere.

Galeria WooCommerce („Galerie produs”): sub fiecare imagine apar numele
fișierului, Attachment ID și un link (iconiță) „Vezi în Media Library”.
Galeria rămâne a WooCommerce (aceleași date, același selector, reordonare și
eliminare); pluginul doar afișează informațiile. Imaginile din galerie, după
imaginea principală, sunt slide-urile din „Pavva – Slider header” (Din produs).

------------------------------------------------------------
DE LA 4.10.30 LA 5.0.0
------------------------------------------------------------
- Aceleași casete, aceleași câmpuri, aceeași salvare: datele deja completate
  rămân și se văd imediat, nimic de reintrodus.
- Widgeturile vechi Elementor nu mai sunt aici: afișarea o fac widgeturile
  din „Pavva Widgets” (Culori și finisaje, Dimensiuni, Acordeon produs…).
  Pe site nu se mai încarcă niciun script sau stil din acest plugin.

------------------------------------------------------------
5.1.0
------------------------------------------------------------
- Scoasă caseta „Imagini slider header”: slider-ul produsului folosește
  imaginea principală + galeria WooCommerce (Pavva Widgets 4.0.1+).
  Imaginile salvate înainte în casetă nu se șterg din baza de date.
- Galeria WooCommerce: nume fișier, ID și link Media Library la fiecare imagine.

------------------------------------------------------------
FIȘIERE
------------------------------------------------------------
pavva-produse.php                   – pornește pluginul
includes/class-pavva-p-fields.php   – casetele produsului (afișare + salvare)
includes/class-pavva-p-gallery-info.php – informațiile din galeria WooCommerce
includes/class-pavva-p-data.php     – citirea datelor
assets/                             – scripturi și stiluri pentru admin
