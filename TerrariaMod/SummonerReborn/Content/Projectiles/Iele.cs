using Microsoft.Xna.Framework;
using SummonerReborn.Content.Buffs;
using Terraria;
using Terraria.ID;
using Terraria.ModLoader;

namespace SummonerReborn.Content.Projectiles
{
	// Minionul Toiagului Ielelor: o zână foarte rapidă și agilă, care lovește des.
	// Vraja ielelor slăbește inamicii: le scade apărarea (Ichor), așa că toți minionii tăi lovesc mai tare.
	public class Iele : MinionZburatorBaza
	{
		protected override int BuffMinion => ModContent.BuffType<IeleBuff>();
		protected override float VitezaAtac => 15f;
		protected override float Inertie => 10f;
		protected override int TimpIntreLoviri => 12;
		protected override Vector3 Lumina => new Vector3(0.9f, 0.5f, 0.9f) * 0.7f;
		protected override int Praf => DustID.PinkTorch;

		public override void OnHitNPC(NPC target, NPC.HitInfo hit, int damageDone) {
			target.AddBuff(BuffID.Ichor, 180);
		}
	}
}
