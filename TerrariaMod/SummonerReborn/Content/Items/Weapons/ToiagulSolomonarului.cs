using SummonerReborn.Content.Buffs;
using SummonerReborn.Content.Projectiles;
using Terraria;
using Terraria.ID;
using Terraria.ModLoader;

namespace SummonerReborn.Content.Items.Weapons
{
	// LATE GAME (după Lunatic Cultist). Cheamă un Norișor de furtună care trage fulgere prin inamici.
	// Comparație vanilla: Stardust Cell Staff și Stardust Dragon Staff (tot din Stardust Fragments).
	public class ToiagulSolomonarului : ToiagMinionBaza
	{
		protected override int Minion => ModContent.ProjectileType<Norisor>();
		protected override int BuffMinion => ModContent.BuffType<NorisorBuff>();

		public override void SetDefaults() {
			SeteazaToiag(damage: 52, knockBack: 3f, mana: 10, useTime: 30, raritate: ItemRarityID.Red, valoare: Item.sellPrice(gold: 10));
		}

		public override void AddRecipes() {
			CreateRecipe()
				.AddIngredient(ItemID.FragmentStardust, 18)
				.AddTile(TileID.LunarCraftingStation) // Ancient Manipulator
				.Register();
		}
	}
}
