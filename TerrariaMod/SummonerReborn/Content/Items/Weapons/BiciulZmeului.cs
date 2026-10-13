using SummonerReborn.Content.Buffs;
using SummonerReborn.Content.Projectiles;
using Terraria;
using Terraria.ID;
using Terraria.Localization;
using Terraria.ModLoader;

namespace SummonerReborn.Content.Items.Weapons
{
	// HARDMODE (după boss-ii mecanici). Inamicii loviți iau foc (Hellfire) și primesc Marca Zmeului.
	// Comparație vanilla: Durendal 40 damage, Firecracker 43 damage.
	public class BiciulZmeului : ModItem
	{
		public override LocalizedText Tooltip => base.Tooltip.WithFormatArgs(MarcaZmeului.TagDamage);

		public override void SetDefaults() {
			Item.DefaultToWhip(ModContent.ProjectileType<BiciulZmeuluiProiectil>(), 44, 3, 5);
			Item.rare = ItemRarityID.Pink;
			Item.value = Item.sellPrice(gold: 5);
		}

		public override bool MeleePrefix() => true;

		public override void AddRecipes() {
			CreateRecipe()
				.AddIngredient(ItemID.HallowedBar, 12)
				.AddIngredient(ItemID.SoulofFright, 8)
				.AddTile(TileID.MythrilAnvil)
				.Register();
		}
	}
}
