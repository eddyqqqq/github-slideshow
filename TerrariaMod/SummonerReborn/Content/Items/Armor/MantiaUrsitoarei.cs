using Terraria;
using Terraria.ID;
using Terraria.Localization;
using Terraria.ModLoader;

namespace SummonerReborn.Content.Items.Armor
{
	// Setul Ursitoarei, piesa de piept.
	[AutoloadEquip(EquipType.Body)] // folosește MantiaUrsitoarei_Body.png
	public class MantiaUrsitoarei : ModItem
	{
		public static readonly int MinioniInPlus = 1;
		public static readonly int DamageSummon = 8; // %

		public override LocalizedText Tooltip => base.Tooltip.WithFormatArgs(MinioniInPlus, DamageSummon);

		public override void SetDefaults() {
			Item.width = 30;
			Item.height = 20;
			Item.value = Item.sellPrice(silver: 80);
			Item.rare = ItemRarityID.Blue;
			Item.defense = 6;
		}

		public override void UpdateEquip(Player player) {
			player.maxMinions += MinioniInPlus;
			player.GetDamage(DamageClass.Summon) += DamageSummon / 100f;
		}

		public override void AddRecipes() {
			foreach (int bara in new[] { ItemID.SilverBar, ItemID.TungstenBar }) {
				CreateRecipe()
					.AddIngredient(bara, 12)
					.AddIngredient(ItemID.Silk, 8)
					.AddTile(TileID.Anvils)
					.Register();
			}
		}
	}
}
