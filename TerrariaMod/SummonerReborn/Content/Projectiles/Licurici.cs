using Microsoft.Xna.Framework;
using SummonerReborn.Content.Buffs;
using Terraria;
using Terraria.ID;
using Terraria.ModLoader;

namespace SummonerReborn.Content.Projectiles
{
	// Minionul Toiagului Licuricilor: zboară prin blocuri și lovește prin contact. Luminează în jur.
	// Lumina lui orbește: uneori inamicii loviți devin confuzi (se mișcă haotic).
	public class Licurici : MinionZburatorBaza
	{
		protected override int BuffMinion => ModContent.BuffType<LicuriciBuff>();
		protected override Vector3 Lumina => new Vector3(0.9f, 0.85f, 0.3f) * 0.8f;
		protected override int Praf => DustID.Firefly;

		public override void OnHitNPC(NPC target, NPC.HitInfo hit, int damageDone) {
			if (Main.rand.NextBool(6))
				target.AddBuff(BuffID.Confused, 90);
		}
	}
}
