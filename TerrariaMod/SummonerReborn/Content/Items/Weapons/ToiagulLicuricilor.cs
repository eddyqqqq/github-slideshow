using SummonerReborn.Content.Buffs;
using SummonerReborn.Content.Projectiles;
using Terraria;
using Terraria.ID;
using Terraria.ModLoader;

namespace SummonerReborn.Content.Items.Weapons
{
	// EARLY GAME. Cheamă un Licurici.
	// Comparație vanilla: Slime Staff 6, Flinx Staff ~8, Vampire Frog Staff 11. Umple golul de dinaintea primului boss.
	public class ToiagulLicuricilor : ToiagMinionBaza
	{
		protected override int Minion => ModContent.ProjectileType<Licurici>();
		protected override int BuffMinion => ModContent.BuffType<LicuriciBuff>();

		public override void SetDefaults() {
			SeteazaToiag(damage: 11, knockBack: 2f, mana: 10, useTime: 36, raritate: ItemRarityID.Blue, valoare: Item.sellPrice(silver: 50));
		}

		public override void AddRecipes() {
			// 12 lemn de orice fel + 3 licurici (prinși cu Bug Net, noaptea), la Work Bench
			CreateRecipe()
				.AddRecipeGroup(RecipeGroupID.Wood, 12)
				.AddIngredient(ItemID.Firefly, 3)
				.AddTile(TileID.WorkBenches)
				.Register();
		}
	}
}
