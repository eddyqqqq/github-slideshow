=== Pavva Widgets ===
Creat de: imagient
Pentru: Pavva
Versiune: 3.2.6
Necesită: WordPress 6.0+, PHP 7.4+, Elementor

Widgeturi Elementor create de imagient special pentru site-ul Pavva.
În Elementor apar în categoria „Pavva”.

------------------------------------------------------------
WIDGETURI
------------------------------------------------------------

1) Pavva – Slider header
   Slider de imagini cu tranziții line, săgeți SVG proprii și text sub slider.
   - Slide-uri nelimitate; imagine separată opțională pentru tabletă și mobil.
   - Culoare de fundal pe fiecare slide (placeholder până vin imaginile).
   - Efecte: alunecare (cu efect de adâncime), estompare, estompare cu zoom.
   - Text sub slider: fix (același pe toate slide-urile) SAU schimbat odată
     cu slide-ul. Câmpurile goale dintr-un slide preiau textul general.
   - Săgeți: implicite, SVG/imagine încărcată sau iconițe din bibliotecă.
   - Animație text cuvânt cu cuvânt (stil GSAP), și la încărcarea paginii.
   - Text în două coloane flexbox (titlu / descriere), complet reglabile responsive;
     fiecare slide are propriul bloc de text, deci nimic nu se mișcă la schimbare.
   - Paginare opțională: puncte sau număr (1 / 3).
   - Autoplay cu pauză la hover, drag cu mouse-ul, swipe pe telefon, tastatură.
   - Toate dimensiunile, culorile, fonturile și spațierile se setează din
     Style, separat pe desktop / tabletă / mobil.
   - Sursă imagini (din 3.2.0): Alese manual (static, implicit, pentru orice
     pagină) / Din produs / Din categorie. Din produs / categorie folosesc
     câmpul „Imagini slider header”; dacă e gol: imaginea + galeria
     WooCommerce (produs) sau Imagine categorie; apoi slide-urile manuale.
   - 3.2.1: Style → Bloc text → „Poziție coloană descriere: Începe sub titlu”
     (iconițe + descriere + buton în coloana din dreapta, sub titlu, ca în
     designul paginii de produs). Iconițele SVG se pot colora (Style → Iconițe).

2) Pavva – Listă interactivă cu imagine
   Listă de avantaje (titlu + descriere) care schimbă lin imaginea la hover / click.
   Pe mobil: carduri glisante lateral (imaginea se schimbă la glisare) sau acordeon.

3) Pavva – Carusel produse
   Carusel de carduri cu drag, alunecare și bară de progres.
   (Înlocuiește vechiul plugin „Carusel Produse pentru Elementor”.)

WIDGETURI DE PRODUS / CATEGORIE (din 3.1.0)
Citesc automat produsul sau categoria paginii: le pui o singură dată
în template și merg pentru toate produsele / categoriile.

4) Pavva – Caracteristici
   Iconițele din „Caracteristici” (doar cele bifate „Afișează în pagina
   produsului”). Aceleași iconițe le poate afișa și Slider header.

5) Pavva – Culori și finisaje
   Imaginea mare cu săgeți și numele culorii + grila de thumbnails.
   Coloane din produs (3 / 4) sau alese în Elementor; alunecare / estompare,
   swipe pe telefon, tragere cu mouse-ul. Pe mobil thumbnails trec sub imagine.
   Săgeți: implicite, din bibliotecă (inclusiv Upload SVG) sau imagine / SVG
   din Media. Efect la selectarea culorii: chenar, inel, ridicare, linie,
   bifă, colț colorat; opțional celelalte estompate și cea activă mărită.
   Numele culorii apare animat la schimbare.

6) Pavva – Dimensiuni și specificații
   Câte o coloană pe dimensiune: imagine în chenar + rânduri Etichetă / Valoare.
   Un rând cu etichetă fără valoare (ex.: „Kombi 4 cm”) apare ca titlu.

7) Pavva – Acordeon produs
   Variante de amenajare (galerie cu mărire), Fișe tehnice și Declarații de
   performanță (PDF), descrieri sau text liber. Deschidere lină.

8) Pavva – Grilă produse (pagina de categorie)
   Carduri cu imagine, nume, săgeată, descriere sinteză și culori +N.
   Categoria paginii automat, categorii alese sau produse alese manual.

PERSONALIZARE
Toate widgeturile de mai sus se pot stiliza complet din Elementor → Style,
separat pe desktop / tabletă / mobil: fonturi, culori, spațieri, padding,
fundaluri, borduri, colțuri rotunjite pe fiecare colț, umbre, filtre pe imagini
și stări Normal / Hover / Activ / Deschis unde au sens.
Culorile din cardurile Grilei: număr maxim, colțuri rotunjite, contor „+N”
cu text propriu (ex.: „+{n} culori”), ca text sau ca pătrățel.

CÂMPUL „IMAGINI SLIDER HEADER” (din 3.2.0)
La produs (casetă separată) și la categorie: alegi mai multe imagini odată,
le ordonezi prin tragere, opțional o imagine verticală pentru mobil.
Câmpul e activ imediat, chiar dacă pluginurile vechi sunt încă active.

FORMULARELE SITE-ULUI (din 3.2.6)
În orice formular de pe site (Elementor, CF7, WPForms…), câmpurile sunt
recunoscute singure după etichetă / prima opțiune / ID:
- „Categorie produs”  → categoriile WooCommerce
- „Produs solicitat”  → produsele, doar din categoria aleasă
- „Județ”             → cele 42 de județe
- „Localitate”        → 13.857 localități (doar din județul ales; dacă alegi
                         întâi localitatea, județul se completează singur)
- „Suprafață proiect” → câmp numeric, clientul scrie suprafața în m²
Prima opțiune scrisă în listă rămâne textul de început.
Linkul „Solicită ofertă” trimite ?produs=…&categorie=…, iar formularul le
selectează singur. Listă localități: date publice (domeniu public).

ÎNAINTE DE ȘTERGEREA PLUGINURILOR VECHI
Pe pagina Plugins, Pavva Widgets arată ce pagini / template-uri mai folosesc
widgeturi din Pavva Products / Pavva Categories și cu ce le înlocuiești.
Când lista e goală, apare mesajul verde: le poți șterge în siguranță.

SECȚIUNI GOALE
Dacă produsul nu are date pentru un widget (ex.: nicio declarație), containerul
Elementor al widgetului se ascunde cu tot cu titlul pus separat (Heading),
direct din CSS, fără salt de pagină. În editor vezi în loc un mesaj.

------------------------------------------------------------
DATE PRODUSE / CATEGORII (din 3.0.0)
------------------------------------------------------------
- Câmpurile de produs (Caracteristici, Culori și finisaje, Dimensiuni, Variante
  de amenajare, Fișe tehnice, Declarații, descrieri) și de categorie (Imagine,
  Text intro, Avantaje, De ce să alegi) sunt incluse în Pavva Widgets.
  Aceleași câmpuri ca pluginurile „Pavva Products” și „Pavva Categories”:
  datele existente se folosesc direct, fără nimic de reintrodus.
- Cât timp pluginurile vechi sunt active, câmpurile din Pavva Widgets stau în
  așteptare (nu apar de două ori). Preiau automat locul la dezactivarea lor.
- Dynamic Tags în Elementor: grupurile „Pavva – Produs” și „Pavva – Categorie”
  (nume, descrieri, imagine categorie, Avantaje, De ce să alegi, link ofertă).
- 3.1.0: Link „Solicită ofertă” apare acum în ambele grupuri (câte un câmp
  separat în fiecare). Linkurile deja setate merg în continuare.
- Slider header: iconițe din Caracteristici produs + buton „Solicită ofertă”.

------------------------------------------------------------
INSTALARE / ACTUALIZARE DE LA „CARUSEL PRODUSE”
------------------------------------------------------------
1. Plugins → Add New → Upload Plugin → pavva-widgets.zip → Install → Activate.
2. Dezactivează și șterge vechiul plugin „Carusel Produse pentru Elementor”.
   Caruselele deja puse în pagini rămân neschimbate, cu toate setările.

------------------------------------------------------------
FIȘIERE
------------------------------------------------------------
pavva-widgets.php                           – pornește pluginul
includes/class-pavva-data.php               – citirea datelor produs / categorie
includes/class-pavva-product-fields.php     – câmpurile produsului (admin)
includes/class-pavva-category-fields.php    – câmpurile categoriei (admin)
includes/class-pavva-dynamic-tags.php       – câmpuri selectabile în Elementor
includes/class-pavva-slider-fields.php      – câmpul Imagini slider header
includes/class-pavva-migration.php          – verificarea widgeturilor vechi
includes/class-pavva-quote-form.php         – datele pentru formulare (categorii / produse)
assets/oferta.js                            – completarea listelor din formulare
assets/data/localitati.json                 – județe și localități
assets/admin/                               – scripturi și stiluri pentru admin
widgets/class-pavva-hero-slider-widget.php  – Slider header: setări + HTML
widgets/class-pavva-carusel-widget.php      – Carusel produse: setări + HTML
widgets/class-pavva-feature-tabs-widget.php – Listă interactivă: setări + HTML
widgets/class-pavva-caracteristici-widget.php – Caracteristici
widgets/class-pavva-culori-widget.php       – Culori și finisaje
widgets/class-pavva-dimensiuni-widget.php   – Dimensiuni și specificații
widgets/class-pavva-acordeon-widget.php     – Acordeon produs
widgets/class-pavva-grila-produse-widget.php – Grilă produse (categorie)
assets/hero-slider.css / hero-slider.js     – stiluri și comportament slider
assets/carusel.css / carusel.js             – stiluri și comportament carusel
assets/feature-tabs.css / feature-tabs.js   – stiluri și comportament listă interactivă
assets/culori.css / culori.js               – Culori și finisaje
assets/acordeon.css / acordeon.js           – Acordeon produs
assets/dimensiuni.css, grila-produse.css, caracteristici.css
assets/sectiuni-goale.css / .js             – ascunderea containerelor goale

© imagient. Plugin realizat de imagient pentru Pavva.
