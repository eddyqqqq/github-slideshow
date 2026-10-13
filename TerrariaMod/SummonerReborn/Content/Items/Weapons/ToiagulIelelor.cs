using SummonerReborn.Content.Buffs;
using SummonerReborn.Content.Projectiles;
using Terraria;
using Terraria.ID;
using Terraria.ModLoader;

namespace SummonerReborn.Content.Items.Weapons
{
	// HARDMODE TIMPURIU (înainte de boss-ii mecanici). Cheamă o Iele: o zână foarte rapidă.
	// Comparație vanilla: Spider Staff 26, Sanguine Staff ~20, Optic Staff (după Twins) 22.
	public class ToiagulIelelor : ToiagMinionBaza
	{
		protected override int Minion => ModContent.ProjectileType<Iele>();
		protected override int BuffMinion => ModContent.BuffType<IeleBuff>();

		public override void SetDefaults() {
			SeteazaToiag(damage: 28, knockBack: 2f, mana: 10, useTime: 30, raritate: ItemRarityID.LightRed, valoare: Item.sellPrice(gold: 4));
		}

		public override void AddRecipes() {
			// Din Hallow: Pixie Dust (de la Pixie), Crystal Shard și Souls of Light
			CreateRecipe()
				.AddIngredient(ItemID.PixieDust, 15)
				.AddIngredient(ItemID.CrystalShard, 10)
				.AddIngredient(ItemID.SoulofLight, 8)
				.AddTile(TileID.MythrilAnvil)
				.Register();
		}
	}
}
