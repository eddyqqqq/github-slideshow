using SummonerReborn.Content.Buffs;
using SummonerReborn.Content.Projectiles;
using Terraria;
using Terraria.ID;
using Terraria.ModLoader;

namespace SummonerReborn.Content.Items.Weapons
{
	// PRE-HARDMODE TÂRZIU. Cheamă un Strigoi (liliac-vampir) care îți dă viață când mușcă.
	// NU se craftează: se găsește în Shadow Chests (Underworld) sau cade rar din Demoni (vezi Common/Systems).
	// Comparație vanilla: Imp Staff 17 damage (tot din Underworld, din Hellstone).
	public class ToiagulStrigoiului : ToiagMinionBaza
	{
		protected override int Minion => ModContent.ProjectileType<Strigoi>();
		protected override int BuffMinion => ModContent.BuffType<StrigoiBuff>();

		public override void SetDefaults() {
			SeteazaToiag(damage: 20, knockBack: 3f, mana: 10, useTime: 30, raritate: ItemRarityID.Orange, valoare: Item.sellPrice(gold: 2));
		}
	}
}
