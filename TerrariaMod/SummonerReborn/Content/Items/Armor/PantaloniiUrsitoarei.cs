using Terraria;
using Terraria.ID;
using Terraria.Localization;
using Terraria.ModLoader;

namespace SummonerReborn.Content.Items.Armor
{
	// Setul Ursitoarei, piesa de picioare.
	[AutoloadEquip(EquipType.Legs)] // folosește PantaloniiUrsitoarei_Legs.png
	public class PantaloniiUrsitoarei : ModItem
	{
		public static readonly int MinioniInPlus = 1;
		public static readonly int VitezaMiscare = 8; // %

		public override LocalizedText Tooltip => base.Tooltip.WithFormatArgs(MinioniInPlus, VitezaMiscare);

		public override void SetDefaults() {
			Item.width = 22;
			Item.height = 18;
			Item.value = Item.sellPrice(silver: 70);
			Item.rare = ItemRarityID.Blue;
			Item.defense = 4;
		}

		public override void UpdateEquip(Player player) {
			player.maxMinions += MinioniInPlus;
			player.moveSpeed += VitezaMiscare / 100f;
		}

		public override void AddRecipes() {
			foreach (int bara in new[] { ItemID.SilverBar, ItemID.TungstenBar }) {
				CreateRecipe()
					.AddIngredient(bara, 10)
					.AddIngredient(ItemID.Silk, 6)
					.AddTile(TileID.Anvils)
					.Register();
			}
		}
	}
}
