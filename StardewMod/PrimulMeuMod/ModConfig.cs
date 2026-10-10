using StardewModdingAPI.Utilities;

namespace PrimulMeuMod
{
    /// <summary>Setările modului, salvate în config.json (creat automat la prima pornire).</summary>
    public sealed class ModConfig
    {
        /// <summary>Tasta care reface energia și viața.</summary>
        public KeybindList RestoreKey { get; set; } = KeybindList.Parse("F5");

        /// <summary>Dacă se afișează un mesaj în joc când se folosește tasta.</summary>
        public bool ShowMessage { get; set; } = true;
    }
}
