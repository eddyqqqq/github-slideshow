using SummonerReborn.Common.Players;
using SummonerReborn.Content.Buffs;
using Terraria;
using Terraria.ID;
using Terraria.Localization;
using Terraria.ModLoader;

namespace SummonerReborn.Content.Items.Accessories
{
	// Mărțișorul: loviturile minionilor îți dau uneori buff-ul Primăvară (regenerare de viață).
	// Rezolvă problema „summonerul n-are healing”. Efectul propriu-zis e în InvocatorPlayer.OnHitNPCWithProj.
	public class Martisor : ModItem
	{
		public override LocalizedText Tooltip => base.Tooltip.WithFormatArgs(
			100 / InvocatorPlayer.SansaMartisor,   // șansa în procente
			Primavara.Regenerare / 2,              // HP pe secundă
			InvocatorPlayer.DurataPrimavara / 60); // secunde

		public override void SetDefaults() {
			Item.width = 28;
			Item.height = 28;
			Item.accessory = true;
			Item.value = Item.sellPrice(gold: 1);
			Item.rare = ItemRarityID.Green;
		}

		public override void UpdateAccessory(Player player, bool hideVisual) {
			player.GetModPlayer<InvocatorPlayer>().Martisor = true;
		}

		public override void AddRecipes() {
			foreach (int bara in new[] { ItemID.GoldBar, ItemID.PlatinumBar }) {
				CreateRecipe()
					.AddIngredient(bara, 3)
					.AddIngredient(ItemID.Silk, 2)
					.AddIngredient(ItemID.Daybloom, 1)
					.AddTile(TileID.Anvils)
					.Register();
			}
		}
	}
}
