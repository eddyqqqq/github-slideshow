# Primul Meu Mod (Stardew Valley / SMAPI)

Un mod simplu: apeși **F5** în joc și îți refaci energia și viața.

## Ce îți trebuie
- Stardew Valley 1.6+
- [SMAPI](https://smapi.io) 4.x
- [.NET 6 SDK](https://dotnet.microsoft.com/download/dotnet/6.0) (sau mai nou)

## Cum îl compilezi și instalezi
În folderul `StardewMod/PrimulMeuMod`, rulează:

```
dotnet build
```

Pachetul `Pathoschild.Stardew.ModBuildConfig` găsește jocul automat și copiază modul
direct în `Stardew Valley/Mods/PrimulMeuMod`. Pornește jocul prin SMAPI și gata.

Dacă nu găsește jocul, adaugă în `PrimulMeuMod.csproj`, în `<PropertyGroup>`:

```xml
<GamePath>C:\Program Files (x86)\Steam\steamapps\common\Stardew Valley</GamePath>
```

## Setări
După prima pornire apare `config.json` în folderul modului:

```json
{
  "RestoreKey": "F5",
  "ShowMessage": true
}
```

Poți pune și combinații, de ex. `"LeftControl + R"`.

## Fișiere
- `manifest.json` – descrierea modului pentru SMAPI
- `ModEntry.cs` – logica modului (evenimente, tasta)
- `ModConfig.cs` – setările din `config.json`
