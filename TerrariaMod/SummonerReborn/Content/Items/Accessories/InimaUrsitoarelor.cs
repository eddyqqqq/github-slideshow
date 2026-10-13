using SummonerReborn.Common.Players;
using Terraria;
using Terraria.ID;
using Terraria.Localization;
using Terraria.ModLoader;

namespace SummonerReborn.Content.Items.Accessories
{
	// LATE GAME. Mărțișorul + Talismanul de Usturoi într-un singur accesoriu, plus un scut:
	// fiecare lovitură a minionilor încarcă scutul. Când ești lovit, scutul îți dă înapoi viața pierdută (până la cât e încărcat).
	// Asta e „mecanismul de supraviețuire legat de loviturile minionilor” din plan.
	public class InimaUrsitoarelor : ModItem
	{
		public static readonly int DamageSummon = 10; // %
		public static readonly int MinioniInPlus = 1;

		public override LocalizedText Tooltip => base.Tooltip.WithFormatArgs(
			InvocatorPlayer.ScutMaxim, DamageSummon, MinioniInPlus);

		public override void SetDefaults() {
			Item.width = 28;
			Item.height = 28;
			Item.accessory = true;
			Item.defense = 4;
			Item.value = Item.sellPrice(gold: 10);
			Item.rare = ItemRarityID.Red;
		}

		public override void UpdateAccessory(Player player, bool hideVisual) {
			var invocator = player.GetModPlayer<InvocatorPlayer>();
			invocator.Martisor = true;            // efectul Mărțișorului
			invocator.TalismanulDeUsturoi = true; // efectul Talismanului
			invocator.InimaUrsitoarelor = true;   // scutul

			player.GetDamage(DamageClass.Summon) += DamageSummon / 100f;
			player.maxMinions += MinioniInPlus;
		}

		public override void AddRecipes() {
			CreateRecipe()
				.AddIngredient<Martisor>()
				.AddIngredient<TalismanulDeUsturoi>()
				.AddIngredient(ItemID.FragmentStardust, 10)
				.AddIngredient(ItemID.SoulofLight, 5)
				.AddTile(TileID.TinkerersWorkbench)
				.Register();
		}
	}
}
