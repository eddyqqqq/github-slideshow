using Microsoft.Xna.Framework;
using SummonerReborn.Content.Buffs;
using Terraria;
using Terraria.ID;
using Terraria.ModLoader;

namespace SummonerReborn.Content.Projectiles
{
	// Biciul Zmeului în zbor: pune Marca Zmeului, dă foc inamicului (Hellfire)
	// și, la 1 din 3 lovituri, zmeul suflă foc: o explozie care lovește inamicii din jur.
	public class BiciulZmeuluiProiectil : BiciProiectilBaza
	{
		protected override int Segmente => 22;
		protected override float LungimeMultiplicator => 1.3f;
		protected override Color CuloareFir => new Color(230, 110, 40);

		protected override void LaLovitura(NPC target) {
			target.AddBuff(ModContent.BuffType<MarcaZmeului>(), 240);
			target.AddBuff(BuffID.OnFire3, 180); // Hellfire, 3 secunde

			if (Main.rand.NextBool(3))
				ExplozieBaza.Creeaza<ExplozieFoc>(Projectile, target.Center, Projectile.damage / 2);
		}
	}
}
