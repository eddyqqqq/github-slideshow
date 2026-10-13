using Microsoft.Xna.Framework;
using SummonerReborn.Content.Buffs;
using Terraria;
using Terraria.ModLoader;

namespace SummonerReborn.Content.Projectiles
{
	// Biciul Solomonarului în zbor: pune Marca Furtunii și îți dă Chemarea Solomonarului
	// (minionii tăi fac mai mult damage cât timp continui să lovești cu biciul: sinergie bici + minioni).
	public class BiciulSolomonaruluiProiectil : BiciProiectilBaza
	{
		protected override int Segmente => 24;
		protected override float LungimeMultiplicator => 1.5f;
		protected override Color CuloareFir => new Color(120, 170, 255);

		protected override void LaLovitura(NPC target) {
			target.AddBuff(ModContent.BuffType<MarcaFurtunii>(), 240);
			Main.player[Projectile.owner].AddBuff(ModContent.BuffType<ChemareaSolomonarului>(), ChemareaSolomonarului.Durata);
		}
	}
}
