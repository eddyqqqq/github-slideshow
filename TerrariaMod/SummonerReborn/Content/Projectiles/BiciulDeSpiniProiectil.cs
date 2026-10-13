using Microsoft.Xna.Framework;
using SummonerReborn.Content.Buffs;
using Terraria;
using Terraria.ModLoader;

namespace SummonerReborn.Content.Projectiles
{
	// Biciul de Spini în zbor: pune Marca Spinilor.
	public class BiciulDeSpiniProiectil : BiciProiectilBaza
	{
		protected override int Segmente => 18;
		protected override float LungimeMultiplicator => 1.1f;
		protected override Color CuloareFir => new Color(90, 160, 60);

		protected override void LaLovitura(NPC target) {
			target.AddBuff(ModContent.BuffType<MarcaSpinilor>(), 240);
		}
	}
}
