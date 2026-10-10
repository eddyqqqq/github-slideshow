# Cooking Book (mod Stardew Valley / SMAPI)

## Ce face
1. **Cooking Book** – o carte nouă la **Pierre (General Store)**, la **10.000g**.
   Când o cumperi, înveți imediat **toate rețetele de gătit** din joc (inclusiv cele din alte moduri).
   Dacă le știi deja pe toate, îți primești banii înapoi.
2. **Charcoal Kiln** (cuptorul de cărbune):
   - 1 lemn (Wood) → **2 cărbuni** (în joc normal: 10 lemne → 1 cărbune)
   - 1 Hardwood → **10 cărbuni**
3. **Semințe de Mahogany** (copacul care dă Hardwood) la Pierre, **500g** bucata.
   Copacul Mahogany crește mai repede (șansă de creștere 50%/zi în loc de 15%).
4. **Mai mult lemn**: orice copac mare dă **+10 lemn** în plus când îl tai,
   iar Mahogany dă și **+10 Hardwood** în plus.

Merge pe salvarea ta existentă.

## Ce îți trebuie
- Stardew Valley 1.6+
- [SMAPI](https://smapi.io) 4.x
- [.NET 6 SDK](https://dotnet.microsoft.com/download/dotnet/6.0) (sau mai nou)

## Instalare (Windows)
1. Descarcă codul: pe GitHub, pe ramura `claude/stardew-valley-smapi-mod-qljo29`,
   apasă **Code → Download ZIP** și dezarhivează-l.
2. Deschide folderul `StardewMod\CookingBook` în Explorer, scrie `cmd` în bara de adresă și apasă Enter.
3. În fereastra neagră, scrie:
   ```
   dotnet build
   ```
4. Gata! Modul se copiază singur în:
   ```
   C:\Program Files (x86)\Steam\steamapps\common\Stardew Valley\Mods\CookingBook
   ```
5. Pornește jocul **prin SMAPI**. Du-te la Pierre: cartea și semințele de Mahogany sunt primele în listă.

### Dacă `dotnet build` nu găsește jocul
Deschide `CookingBook.csproj` cu Notepad și adaugă în `<PropertyGroup>`, cu calea ta:
```xml
<GamePath>C:\Program Files (x86)\Steam\steamapps\common\Stardew Valley</GamePath>
```

## Setări (`config.json`)
Apare în `Mods\CookingBook` după prima pornire a jocului. Îl editezi cu Notepad (cu jocul închis):
```json
{
  "CookingBookPrice": 10000,
  "MahoganySeedPrice": 500,
  "CoalPerWood": 2,
  "CoalPerHardwood": 10,
  "ExtraWoodPerTree": 10,
  "ExtraHardwoodPerMahogany": 10,
  "MahoganyGrowthChance": 0.5
}
```

## Fișiere
- `manifest.json` – descrierea modului pentru SMAPI
- `ModEntry.cs` – logica modului
- `ModConfig.cs` – setările din `config.json`
- `assets/cooking-book.png` – iconița cărții
