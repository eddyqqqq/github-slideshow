using Microsoft.Xna.Framework;
using SummonerReborn.Content.Buffs;
using Terraria;
using Terraria.ID;
using Terraria.ModLoader;

namespace SummonerReborn.Content.Projectiles
{
	// Minionul Toiagului Strigoiului: un liliac-vampir. Când mușcă, uneori îți dă viață
	// și blestemă inamicul cu flăcări de umbră (Shadowflame).
	public class Strigoi : MinionZburatorBaza
	{
		public const int SansaVindecare = 4; // 1 din 4 mușcături
		public const int Vindecare = 2;      // HP

		protected override int BuffMinion => ModContent.BuffType<StrigoiBuff>();
		protected override int Latime => 20;
		protected override float VitezaAtac => 11f;
		protected override Vector3 Lumina => new Vector3(0.6f, 0.05f, 0.1f) * 0.6f;
		protected override int Praf => DustID.RedTorch;

		public override void OnHitNPC(NPC target, NPC.HitInfo hit, int damageDone) {
			// Nu fură viață din manechine (Target Dummy) sau din creaturi inofensive.
			if (target.immortal || target.friendly || target.lifeMax <= 5)
				return;

			target.AddBuff(BuffID.ShadowFlame, 120);

			if (Main.rand.NextBool(SansaVindecare))
				Main.player[Projectile.owner].Heal(Vindecare);
		}
	}
}
