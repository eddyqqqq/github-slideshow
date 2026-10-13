using SummonerReborn.Content.Buffs;
using SummonerReborn.Content.Projectiles;
using Terraria;
using Terraria.ID;
using Terraria.Localization;
using Terraria.ModLoader;

namespace SummonerReborn.Content.Items.Weapons
{
	// EARLY GAME. Bici pentru începutul jocului. Inamicii loviți primesc marca „Spini”:
	// minionii fac damage în plus pe ei (vezi Content/Buffs/MarcaSpinilor.cs).
	// Comparație vanilla: Leather Whip 7 damage și 4 tag damage. Snapthorn e mai puternic, dar vine mai târziu.
	public class BiciulDeSpini : ModItem
	{
		// {0} din descriere se înlocuiește cu tag damage-ul.
		public override LocalizedText Tooltip => base.Tooltip.WithFormatArgs(MarcaSpinilor.TagDamage);

		public override void SetDefaults() {
			// proiectil, damage, knockback, viteză
			Item.DefaultToWhip(ModContent.ProjectileType<BiciulDeSpiniProiectil>(), 13, 2, 4);
			Item.rare = ItemRarityID.Blue;
			Item.value = Item.sellPrice(silver: 50);
		}

		// Biciul primește prefixe de melee (Legendary etc.), ca biciurile din vanilla.
		public override bool MeleePrefix() {
			return true;
		}

		public override void AddRecipes() {
			// 3 liane (Vine, din junglă) + 15 cactuși, la Work Bench
			CreateRecipe()
				.AddIngredient(ItemID.Vine, 3)
				.AddIngredient(ItemID.Cactus, 15)
				.AddTile(TileID.WorkBenches)
				.Register();
		}
	}
}
