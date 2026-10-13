# Renasterea Invocatorului (SummonerReborn) – mod tModLoader

Face clasa Summoner mai utilă și mai distractivă, de la începutul jocului până la Moon Lord.
Țintește **tModLoader stabil (Terraria 1.4.4)**. Portarea pentru 1.4.5 nu e încă gata.

## Ce conține (toată progresia)
Itemele sunt pe etape, ca să ai mereu ceva nou de summoner. Damage-ul e comparat cu itemele vanilla de la aceeași etapă.

### Early game (înainte de primul boss)
| Item | Ce face | Rețetă |
|---|---|---|
| **Toiagul Licuricilor** | minion, 11 damage: un licurici luminos | 12 Wood + 3 Firefly @ Work Bench |
| **Biciul de Spini** | bici, 13 damage, +5 tag damage | 3 Vine + 15 Cactus @ Work Bench |
| **Setul Ursitoarei** (Gluga, Mantia, Pantalonii) | 14 apărare total, +1 minion pe piesă. **Set:** stai nemișcat 2 s → +25% damage summon | Silver/Tungsten Bar + Silk @ Anvil (8+5 / 12+8 / 10+6) |
| **Mărțișor** | 25% șansă la lovitura unui minion: regenerezi 4 HP/s timp de 3 s | 3 Gold/Platinum Bar + 2 Silk + 1 Daybloom @ Anvil |
| **Talismanul de Usturoi** | când ești lovit: minionii +20% damage 5 s, +0.5 s invulnerabilitate, +2 apărare | 3 Daybloom + 2 Chain + 2 Flinx Fur @ Anvil |

### Pre-Hardmode târziu
| Item | Ce face | Cum îl obții |
|---|---|---|
| **Toiagul Strigoiului** | minion, 20 damage: un liliac-vampir care îți dă 2 viață la 1 din 4 mușcături | **Shadow Chests** din Underworld (în lumi noi, cam 1 din 3 cufere) sau **1/35 din Demoni / Voodoo Demoni** (merge și pe lumi vechi) |

### Hardmode
| Item | Ce face | Rețetă |
|---|---|---|
| **Toiagul Ielelor** | minion, 28 damage: o zână foarte rapidă, lovește des | 15 Pixie Dust + 10 Crystal Shard + 8 Soul of Light @ Mythril/Orichalcum Anvil |
| **Biciul Zmeului** | bici, 44 damage, +8 tag damage, dă foc inamicilor (Hellfire) | 12 Hallowed Bar + 8 Soul of Fright @ Mythril/Orichalcum Anvil |
| **Cununa de Clorofită** | cască de summoner: 10 apărare, +2 minioni, +15% damage. Se poartă cu **Chlorophyte Plate Mail + Chlorophyte Greaves** din vanilla. **Set:** cristal de frunze + 1 minion | 12 Chlorophyte Bar @ Mythril/Orichalcum Anvil |

### Late game (după Lunatic Cultist)
| Item | Ce face | Rețetă |
|---|---|---|
| **Toiagul Solomonarului** | minion, 52 damage: un nor de furtună care stă deasupra inamicilor și trage fulgere care trec prin 3 inamici | 18 Stardust Fragment @ Ancient Manipulator |
| **Biciul Solomonarului** | bici, 85 damage, +14 tag damage, +10% critice pentru minioni pe ținte marcate. **Sinergie:** când lovești, minionii primesc +15% damage 5 s | 18 Stardust Fragment @ Ancient Manipulator |
| **Inima Ursitoarelor** | Mărțișor + Talisman într-unul singur, +10% damage, +1 minion, +4 apărare. **Scut:** loviturile minionilor încarcă un scut de până la 60 viață, care ți-o dă înapoi când ești lovit | Mărțișor + Talismanul de Usturoi + 10 Stardust Fragment + 5 Soul of Light @ Tinkerer's Workbench |

### Pentru toți minionii (și cei din vanilla)
Când e un boss în viață și n-ai ales tu o țintă, **minionii atacă boss-ul**.

### Cu ce am comparat (balans)
Valorile vanilla sunt verificate pe wiki-ul Terraria acolo unde l-am putut accesa, iar restul sunt din ghiduri de summoner.
- **Early:** Slime Staff 6, Flinx Staff ~8, Vampire Frog Staff 11, Leather Whip 7 (4 tag). Bee armor 13 apărare și +2 minioni.
- **Pre-HM târziu:** Imp Staff 17.
- **Hardmode:** Spider Staff 26, Optic Staff 22, Durendal 40, Firecracker 43. Spooky armor +4 minioni.
- **Late:** Stardust Cell / Dragon Staff, Morning Star 55, Dark Harvest 60, Kaleidoscope 180 (după Empress).
- **Setul Ursitoarei e încă intenționat puternic** pentru partea de început (+3 minioni). Îl reglăm după ce testezi.

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
  - toate itemele din mod (de la early game la late game);
  - cel mai bun build de summoner din vanilla: Stardust armor, Terraprisma, Stardust Dragon, Kaleidoscope, Papyrus Scarab, Necromantic Scroll etc.;
  - itemele care cheamă boss-ii (câte 5);
  - poțiuni, Life Crystal și Life Fruit pentru viață maximă.

  Ce nu încape în inventar cade pe jos, lângă tine.
- Comanda de chat **`/kittest`** îți dă kit-ul din nou, oricând.
- **Pentru boss-ii de Hardmode** ai nevoie de o lume de Hardmode. Cel mai simplu e să faci o lume nouă și să omori Wall of Flesh cu Guide Voodoo Doll, aruncată în lavă în Underworld.

## Mod OP (Binecuvantarea Ursitoarelor)
Face **Master Mode mai ușor** pentru summoner, dar **progresia rămâne normală**. Itemele nu cresc singure, deci tot trebuie să-ți faci echipament nou pentru fiecare etapă.
Bonusurile sunt moderate: cam cât ți-ar da o piesă de armură și un accesoriu în plus.
**Pornire:** Mods → Renasterea Invocatorului → rotița de setări → **Mod OP**. Merge imediat, fără reload, și îl poți opri oricând.

| Bonus | Valoare |
|---|---|
| Damage de summon | +15% |
| Minioni | +1 |
| Apărare | +6 |
| Damage primit | -10% |
| Viață maximă | +40 |
| Regenerare | +1 HP/s |
| Viteză bici | +10% |
| Furt de viață | minionii îți dau 2% din damage-ul lor ca viață (max 2 HP, de 2 ori pe secundă) |
| Salvare de la moarte | o dată la 5 minute: în loc să mori, rămâi cu 1/4 din viață și 1.5 secunde de invulnerabilitate |

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
│  ├─ GlobalNPCs/MarciBiciNPC.cs      efectul mărcilor de bici (damage în plus pentru minioni)
│  ├─ Systems/StrigoiLootSystem.cs    Toiagul Strigoiului în Shadow Chests + drop din Demoni
│  └─ Commands/KitCommand.cs          comanda /kittest
└─ Content/
   ├─ Items/Weapons/       toiegele și biciurile (ToiagMinionBaza = baza comună a toiegelor)
   ├─ Items/Armor/         setul Ursitoarei, Cununa de Clorofită
   ├─ Items/Accessories/   Mărțișor, Talismanul de Usturoi, Inima Ursitoarelor
   ├─ Projectiles/         minionii (MinionZburatorBaza = baza comună) și biciurile în zbor (BiciProiectilBaza)
   └─ Buffs/               buff-urile minionilor, mărcile de bici, Primavara, Furie, Chemarea Solomonarului...
```
Cifrele de balans sunt sus, în fiecare fișier, ca `public static readonly int ...`. Le schimbi acolo și dai **Build + Reload**.

## Înlocuirea imaginilor (sprite-uri)
Fiecare `.png` stă lângă `.cs`-ul cu același nume. Desenează peste el, păstrând **numele** și **dimensiunea**, apoi dai **Build + Reload**.
- `*_Head.png`, `*_Body.png`, `*_Legs.png` sunt armura purtată pe personaj, cu toate cadrele de animație. Sunt recolorate din ExampleMod-ul oficial (licență MIT), ca să aibă formatul corect.
- `BiciulDeSpiniProiectil.png` e tot recolorat din ExampleMod. Bucățile din el (mâner, segmente, vârf) trebuie să rămână la aceleași înălțimi.
- Minionii (`Licurici.png`, `Iele.png`, `Strigoi.png`, `Norisor.png`) au 4 cadre de animație, unul sub altul.
- Toate imaginile de bici (`Biciul...Proiectil.png`) au același format ca cel din ExampleMod.

## Texte
Terraria nu are limba română, așa că textele românești stau în fișierul de engleză. Sunt scrise **fără diacritice**, pentru că fontul jocului probabil nu are literele ă, ș, ț.
