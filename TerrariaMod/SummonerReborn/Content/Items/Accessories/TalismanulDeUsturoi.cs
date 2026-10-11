using SummonerReborn.Common.Players;
using SummonerReborn.Content.Buffs;
using Terraria;
using Terraria.ID;
using Terraria.Localization;
using Terraria.ModLoader;

namespace SummonerReborn.Content.Items.Accessories
{
	// Talismanul de Usturoi (ține strigoii departe): când ești lovit, minionii intră în Furie
	// și primești puțină invulnerabilitate în plus. Efectul e în InvocatorPlayer.PostHurt.
	public class TalismanulDeUsturoi : ModItem
	{
		public static readonly int Aparare = 2;

		public override LocalizedText Tooltip => base.Tooltip.WithFormatArgs(
			(int)(Furie.BonusDamage * 100),
			InvocatorPlayer.DurataFurie / 60,
			Aparare);

		public override void SetDefaults() {
			Item.width = 28;
			Item.height = 28;
			Item.accessory = true;
			Item.defense = Aparare;
			Item.value = Item.sellPrice(gold: 1);
			Item.rare = ItemRarityID.Green;
		}

		public override void UpdateAccessory(Player player, bool hideVisual) {
			player.GetModPlayer<InvocatorPlayer>().TalismanulDeUsturoi = true;
		}

		public override void AddRecipes() {
			// Usturoi nu există în Terraria, așa că folosim o floare + lanțuri + blană de Flinx.
			CreateRecipe()
				.AddIngredient(ItemID.Daybloom, 3)
				.AddIngredient(ItemID.Chain, 2)
				.AddIngredient(ItemID.FlinxFur, 2)
				.AddTile(TileID.Anvils)
				.Register();
		}
	}
}
