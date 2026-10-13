using SummonerReborn.Content.Buffs;
using SummonerReborn.Content.Projectiles;
using Terraria;
using Terraria.ID;
using Terraria.Localization;
using Terraria.ModLoader;

namespace SummonerReborn.Content.Items.Weapons
{
	// LATE GAME (după Lunatic Cultist). Sinergie bici + minioni:
	// marchează inamicii (damage și critice în plus pentru minioni) și îți dă Chemarea Solomonarului (+15% damage minioni).
	// Comparație vanilla: Morning Star 55, Dark Harvest 60; Kaleidoscope (180) vine abia după Empress.
	public class BiciulSolomonarului : ModItem
	{
		public override LocalizedText Tooltip => base.Tooltip.WithFormatArgs(
			MarcaFurtunii.TagDamage,
			MarcaFurtunii.SansaCritica,
			(int)(ChemareaSolomonarului.BonusDamage * 100));

		public override void SetDefaults() {
			Item.DefaultToWhip(ModContent.ProjectileType<BiciulSolomonaruluiProiectil>(), 85, 3, 6);
			Item.rare = ItemRarityID.Red;
			Item.value = Item.sellPrice(gold: 10);
		}

		public override bool MeleePrefix() => true;

		public override void AddRecipes() {
			CreateRecipe()
				.AddIngredient(ItemID.FragmentStardust, 18)
				.AddTile(TileID.LunarCraftingStation) // Ancient Manipulator
				.Register();
		}
	}
}
