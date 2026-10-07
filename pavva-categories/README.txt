=== Pavva Categories ===
Creat de: imagient
Pentru: Pavva
Versiune: 2.0.0
Necesită: WordPress 6.0+, PHP 7.4+, WooCommerce
Lucrează împreună cu: Pavva Widgets 4.0.0+ (afișarea pe site)

Câmpurile din pagina de editare a categoriei de produse WooCommerce:
- Imagine categorie
- Text intro
- Titlu + Conținut „Avantaje”
- Titlu + Conținut „De ce să alegi”
- Imagini slider header

În Elementor le folosești prin Dynamic Tags, grupul „Pavva – Categorie”
(din Pavva Widgets), și prin widgetul „Pavva – Slider header”.

------------------------------------------------------------
DE LA 1.0.2 LA 2.0.0
------------------------------------------------------------
- Aceleași câmpuri și aceeași salvare: datele deja completate rămân.
- Widgeturile vechi Elementor nu mai sunt aici (le înlocuiesc Dynamic Tags
  din Pavva Widgets).
- Nou: câmpul „Imagini slider header” (era în Pavva Widgets 3.x).

------------------------------------------------------------
FIȘIERE
------------------------------------------------------------
pavva-categories.php                – pornește pluginul
includes/class-pavva-c-fields.php   – câmpurile categoriei (afișare + salvare)
includes/class-pavva-c-slider.php   – câmpul Imagini slider header
includes/class-pavva-c-data.php     – citirea datelor
assets/                             – scripturi și stiluri pentru admin
