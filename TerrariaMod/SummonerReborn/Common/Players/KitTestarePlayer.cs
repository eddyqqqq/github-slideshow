using Microsoft.Xna.Framework;
using SummonerReborn.Common.Configs;
using SummonerReborn.Content.Items.Accessories;
using SummonerReborn.Content.Items.Armor;
using SummonerReborn.Content.Items.Weapons;
using Terraria;
using Terraria.DataStructures;
using Terraria.ID;
using Terraria.ModLoader;
using Terraria.ModLoader.IO;

namespace SummonerReborn.Common.Players
{
	// Dă kit-ul de testare: toate itemele din mod + cel mai bun echipament de summoner din vanilla + itemele care cheamă boss-ii.
	public class KitTestarePlayer : ModPlayer
	{
		// Ține minte (în salvarea personajului) dacă personajul a primit deja kit-ul.
		public bool KitPrimit;

		public override void SaveData(TagCompound tag) {
			tag["kitPrimit"] = KitPrimit;
		}

		public override void LoadData(TagCompound tag) {
			KitPrimit = tag.GetBool("kitPrimit");
		}

		public override void OnEnterWorld() {
			if (Player.whoAmI != Main.myPlayer || KitPrimit || !ModContent.GetInstance<ConfigTestare>().ModTestare)
				return;

			DaKit();
			KitPrimit = true;
		}

		// Folosit și de comanda /kittest.
		public void DaKit() {
			var sursa = new EntitySource_Gift(Player);

			// Toate itemele din mod, în ordinea progresiei
			int[] iteme_mod = {
				ModContent.ItemType<ToiagulLicuricilor>(),
				ModContent.ItemType<BiciulDeSpini>(),
				ModContent.ItemType<GlugaUrsitoarei>(),
				ModContent.ItemType<MantiaUrsitoarei>(),
				ModContent.ItemType<PantaloniiUrsitoarei>(),
				ModContent.ItemType<Martisor>(),
				ModContent.ItemType<TalismanulDeUsturoi>(),
				ModContent.ItemType<ToiagulStrigoiului>(),
				ModContent.ItemType<ToiagulIelelor>(),
				ModContent.ItemType<BiciulZmeului>(),
				ModContent.ItemType<CununaDeClorofita>(),
				ItemID.ChlorophytePlateMail, ItemID.ChlorophyteGreaves, // cu Cununa formează setul
				ModContent.ItemType<ToiagulSolomonarului>(),
				ModContent.ItemType<BiciulSolomonarului>(),
				ModContent.ItemType<InimaUrsitoarelor>(),
			};

			// Cel mai bun build de summoner din vanilla (end-game)
			int[] build_vanilla = {
				ItemID.StardustHelmet, ItemID.StardustBreastplate, ItemID.StardustLeggings,
				ItemID.EmpressBlade,          // Terraprisma
				ItemID.StardustDragonStaff,
				ItemID.RainbowCrystalStaff,   // santinelă
				ItemID.MoonlordTurretStaff,   // Lunar Portal Staff
				ItemID.RainbowWhip,           // Kaleidoscope
				ItemID.FireWhip,              // Firecracker
				ItemID.ScytheWhip,            // Dark Harvest
				ItemID.PapyrusScarab, ItemID.NecromanticScroll, ItemID.AvengerEmblem,
				ItemID.WingsStardust, ItemID.AnkhShield, ItemID.MasterNinjaGear, ItemID.TerrasparkBoots,
				ItemID.BewitchingTable,
			};

			// Itemele care cheamă boss-ii, în ordinea progresiei
			int[] boss_iteme = {
				ItemID.SlimeCrown, ItemID.SuspiciousLookingEye, ItemID.WormFood, ItemID.BloodySpine,
				ItemID.Abeemination, ItemID.DeerThing, ItemID.ClothierVoodooDoll, ItemID.GuideVoodooDoll,
				ItemID.QueenSlimeCrystal, ItemID.MechanicalEye, ItemID.MechanicalWorm, ItemID.MechanicalSkull,
				ItemID.LihzahrdPowerCell, ItemID.TruffleWorm, ItemID.EmpressButterfly, ItemID.CelestialSigil,
			};

			foreach (int tip in iteme_mod)
				Player.QuickSpawnItem(sursa, tip);
			foreach (int tip in build_vanilla)
				Player.QuickSpawnItem(sursa, tip);
			foreach (int tip in boss_iteme)
				Player.QuickSpawnItem(sursa, tip, 5);

			// Poțiuni și viață/mană maximă (personajele noi au doar 100 HP)
			Player.QuickSpawnItem(sursa, ItemID.SummoningPotion, 30);
			Player.QuickSpawnItem(sursa, ItemID.WrathPotion, 30);
			Player.QuickSpawnItem(sursa, ItemID.RagePotion, 30);
			Player.QuickSpawnItem(sursa, ItemID.IronskinPotion, 30);
			Player.QuickSpawnItem(sursa, ItemID.RegenerationPotion, 30);
			Player.QuickSpawnItem(sursa, ItemID.EndurancePotion, 30);
			Player.QuickSpawnItem(sursa, ItemID.HeartreachPotion, 30);
			Player.QuickSpawnItem(sursa, ItemID.LifeforcePotion, 30);
			Player.QuickSpawnItem(sursa, ItemID.SuperHealingPotion, 60);
			Player.QuickSpawnItem(sursa, ItemID.LifeCrystal, 15);
			Player.QuickSpawnItem(sursa, ItemID.LifeFruit, 20);
			Player.QuickSpawnItem(sursa, ItemID.ManaCrystal, 9);

			Main.NewText($"Renasterea Invocatorului v{Mod.Version}: ai primit kit-ul de testare! (Ce nu incape in inventar e pe jos, langa tine.)", Color.LightGreen);
		}
	}
}
