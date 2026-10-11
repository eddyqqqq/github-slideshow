using System.ComponentModel;
using Terraria.ModLoader.Config;

namespace SummonerReborn.Common.Configs
{
	// Setările mod-ului. Le schimbi din joc: Mods -> butonul de configurare (rotița) de la Renasterea Invocatorului.
	// Textele afișate sunt în Localization/en-US_Mods.SummonerReborn.hjson.
	public class ConfigTestare : ModConfig
	{
		public override ConfigScope Mode => ConfigScope.ServerSide;

		// Când e pornit: la prima intrare pe o lume, fiecare personaj primește kit-ul de testare.
		// Oprește-l când termini testarea și vrei să joci normal, de la zero.
		[DefaultValue(true)]
		public bool ModTestare;

		// Când e pornit: dacă e un boss în viață și n-ai ales tu o țintă, minionii atacă boss-ul.
		[DefaultValue(true)]
		public bool TintireAutomataBoss;
	}
}
