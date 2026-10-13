# Renasterea Invocatorului (SummonerReborn) – mod tModLoader

Face clasa Summoner mai utilă și mai distractivă. **Faza 1: începutul jocului.**
Țintește **tModLoader stabil (Terraria 1.4.4)**. Portarea pentru 1.4.5 nu e încă gata.

## Ce conține Faza 1
| Item | Tip | Ce face | Rețetă |
|---|---|---|---|
| Toiagul Licuricilor | toiag de minion | 11 damage, 1 slot. Cheamă un licurici care zboară prin blocuri și lovește | 12 lemn + 3 Firefly @ Work Bench |
| Biciul de Spini | bici | 13 damage, +5 tag damage pentru minioni | 3 Vine + 15 Cactus @ Work Bench |
| Gluga Ursitoarei | armură cap | 4 apărare, +1 minion, +5% damage summon | 8 Silver/Tungsten Bar + 5 Silk @ Anvil |
| Mantia Ursitoarei | armură piept | 6 apărare, +1 minion, +8% damage summon | 12 Silver/Tungsten Bar + 8 Silk @ Anvil |
| Pantalonii Ursitoarei | armură picioare | 4 apărare, +1 minion, +8% viteză | 10 Silver/Tungsten Bar + 6 Silk @ Anvil |
| **Bonus set Ursitoare** | | stai nemișcat 2 secunde → +25% damage summon | |
| Mărțișor | accesoriu | 25% șansă la lovitura unui minion: regenerezi 4 HP/s timp de 3 s | 3 Gold/Platinum Bar + 2 Silk + 1 Daybloom @ Anvil |
| Talismanul de Usturoi | accesoriu | când ești lovit: minionii +20% damage 5 s, +0.5 s invulnerabilitate, +2 apărare | 3 Daybloom + 2 Chain + 2 Flinx Fur @ Anvil |

**Și pentru minionii din vanilla:** când e un boss în viață și n-ai ales tu o țintă, minionii atacă boss-ul.

### Cu ce am comparat (balans)
Valorile vanilla sunt verificate pe wiki-ul Terraria acolo unde l-am putut accesa, iar restul sunt din ghiduri de summoner.
- Arme: Slime Staff 6, Flinx Staff ~8, Vampire Frog Staff 11, Hornet Staff 12, Imp Staff 17 damage. Leather Whip 7 damage și 4 tag.
- Armuri: Bee armor 13 apărare și +2 minioni. Obsidian 15 apărare și +1 minion.
- **Setul Ursitoarei e intenționat prea puternic** pentru faza de test: 14 apărare și +3 minioni, mai devreme decât Bee armor. Îl reglăm după ce testezi.

## Ce trebuie instalat
1. **tModLoader** din Steam (gratuit).
2. **.NET 8.0 SDK**: https://dotnet.microsoft.com/download/dotnet/8.0. Cel de Stardew (.NET 6) nu e suficient.
3. Opțional, pentru editat cod: **Visual Studio 2022 Community** sau **VS Code**.

## Instalare și build
1. Pornește tModLoader și intră o dată în **Workshop → Develop Mods**. Asta creează folderul `ModSources`.
2. Copiază folderul `SummonerReborn` (cel în care e fișierul ăsta) în:
   `Documente\My Games\Terraria\tModLoader\ModSources\`
   - Dacă ai OneDrive, poate fi `C:\Users\Pro\OneDrive\Documente\My Games\...`
   - Folderul trebuie să se numească exact `SummonerReborn`.
3. În tModLoader: **Workshop → Develop Mods**, găsește *Renasterea Invocatorului* și apasă **Build + Reload**.
4. Dacă apar erori (text roșu), copiază-le tot și trimite-le. Dacă nu, mod-ul apare în meniul **Mods** și e activat.

## Testare
- **Mod de testare** (pornit din start): prima dată când intri cu un personaj pe o lume, primești automat:
  - toate itemele din mod;
  - cel mai bun build de summoner din vanilla: Stardust armor, Terraprisma, Stardust Dragon, Kaleidoscope, Papyrus Scarab, Necromantic Scroll etc.;
  - itemele care cheamă boss-ii (câte 5);
  - poțiuni, Life Crystal și Life Fruit pentru viață maximă.

  Ce nu încape în inventar cade pe jos, lângă tine.
- Comanda de chat **`/kittest`** îți dă kit-ul din nou, oricând.
- **Pentru boss-ii de Hardmode** ai nevoie de o lume de Hardmode. Cel mai simplu e să faci o lume nouă și să omori Wall of Flesh cu Guide Voodoo Doll, aruncată în lavă în Underworld.

## Mod OP (Binecuvantarea Ursitoarelor)
O variantă în care summonerul e **foarte puternic**: poți face toți boss-ii pe **Master Mode** cu itemele de început din mod.
**Pornire:** Mods → Renasterea Invocatorului → rotița de setări → **Mod OP**. Merge imediat, fără reload, și îl poți opri oricând.

| Bonus | Valoare |
|---|---|
| Damage de summon | +40% |
| Minioni / santinele | +2 / +1 |
| Apărare | +12 |
| Damage primit | -15% |
| Viață maximă | +60 |
| Regenerare | +2 HP/s |
| Viteză de mișcare / viteză bici | +10% / +20% |
| **Armele din mod** (Toiagul Licuricilor, Biciul de Spini) | **+30% damage pentru fiecare boss învins** (x1 la început, ~x6 după Moon Lord) |
| **Setul Ursitoarei** | +2 apărare pentru fiecare boss învins, +1 minion la fiecare 4 boss-i |
| Furt de viață | minionii îți dau 3% din damage-ul lor ca viață (max 4 HP, de 3 ori pe secundă) |
| Salvare de la moarte | o dată la 3 minute: în loc să mori, rămâi cu 1/3 din viață și 2 secunde de invulnerabilitate |

Când intri pe lume, chat-ul îți spune cât de mult damage fac armele din mod la progresul tău.
Toate cifrele sunt sus în `Common/Players/ModOPPlayer.cs`, dacă vrei să-l faci mai ușor sau mai greu.

## Oprirea fazei de test
Mergi la **Mods → Renasterea Invocatorului → rotița de setări** și oprește **Mod de testare**. Nu trebuie să umbli în cod.
Personajele care au primit deja kit-ul îl păstrează. Pentru un început curat, fă un personaj și o lume noi.
Țintirea automată pe boss se oprește tot de acolo.

## Ce face fiecare fișier
```
SummonerReborn/
├─ build.txt                     nume, autor, versiune (pentru tModLoader)
├─ description.txt               descrierea din meniul Mods
├─ icon.png                      iconița mod-ului (80x80)
├─ SummonerReborn.cs             clasa principală (goală: restul se încarcă singur)
├─ SummonerReborn.csproj         spune compilatorului cum să facă build-ul
├─ Localization/en-US_Mods.SummonerReborn.hjson   TOATE textele: nume, descrieri, setări
├─ Common/
│  ├─ Configs/ConfigTestare.cs        setările „Mod de testare” și „țintire automată”
│  ├─ Players/KitTestarePlayer.cs     dă kit-ul de testare
│  ├─ Players/InvocatorPlayer.cs      efectele accesoriilor, bonusul de set, țintirea pe boss
│  ├─ Players/ModOPPlayer.cs          varianta Mod OP (toate bonusurile și cifrele ei)
│  └─ Commands/KitCommand.cs          comanda /kittest
└─ Content/
   ├─ Items/Weapons/       Toiagul Licuricilor, Biciul de Spini
   ├─ Items/Armor/         cele 3 piese ale setului Ursitoarei
   ├─ Items/Accessories/   Mărțișor, Talismanul de Usturoi
   ├─ Projectiles/         Licurici (minionul), BiciulDeSpiniProiectil (biciul în zbor)
   └─ Buffs/               LicuriciBuff, MarcaSpinilor (+ efectul ei), Primavara, Furie, UrsitoareleObosite
```
Cifrele de balans sunt sus, în fiecare fișier, ca `public static readonly int ...`. Le schimbi acolo și dai **Build + Reload**.

## Înlocuirea imaginilor (sprite-uri)
Fiecare `.png` stă lângă `.cs`-ul cu același nume. Desenează peste el, păstrând **numele** și **dimensiunea**, apoi dai **Build + Reload**.
- `*_Head.png`, `*_Body.png`, `*_Legs.png` sunt armura purtată pe personaj, cu toate cadrele de animație. Sunt recolorate din ExampleMod-ul oficial (licență MIT), ca să aibă formatul corect.
- `BiciulDeSpiniProiectil.png` e tot recolorat din ExampleMod. Bucățile din el (mâner, segmente, vârf) trebuie să rămână la aceleași înălțimi.
- `Licurici.png` are 4 cadre de 16x16, unul sub altul.

## Texte
Terraria nu are limba română, așa că textele românești stau în fișierul de engleză. Sunt scrise **fără diacritice**, pentru că fontul jocului probabil nu are literele ă, ș, ț.
