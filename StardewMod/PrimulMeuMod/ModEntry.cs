using StardewModdingAPI;
using StardewModdingAPI.Events;
using StardewValley;

namespace PrimulMeuMod
{
    /// <summary>Punctul de intrare al modului.</summary>
    internal sealed class ModEntry : Mod
    {
        private ModConfig Config = null!;

        /// <summary>Apelat de SMAPI o singură dată, când se încarcă modul.</summary>
        public override void Entry(IModHelper helper)
        {
            this.Config = helper.ReadConfig<ModConfig>();

            helper.Events.GameLoop.SaveLoaded += this.OnSaveLoaded;
            helper.Events.Input.ButtonsChanged += this.OnButtonsChanged;
        }

        private void OnSaveLoaded(object? sender, SaveLoadedEventArgs e)
        {
            this.Monitor.Log($"Salvare încărcată pentru {Game1.player.Name}. Apasă {this.Config.RestoreKey} ca să-ți refaci energia.", LogLevel.Info);
        }

        private void OnButtonsChanged(object? sender, ButtonsChangedEventArgs e)
        {
            // ignoră tasta dacă nu suntem în joc sau dacă e deschis un meniu / chat
            if (!Context.IsPlayerFree)
                return;

            if (!this.Config.RestoreKey.JustPressed())
                return;

            Farmer player = Game1.player;
            player.Stamina = player.MaxStamina;
            player.health = player.maxHealth;

            if (this.Config.ShowMessage)
                Game1.addHUDMessage(new HUDMessage("Energie și viață refăcute!", HUDMessage.health_type));

            this.Monitor.Log("Energie și viață refăcute.", LogLevel.Debug);
        }
    }
}
