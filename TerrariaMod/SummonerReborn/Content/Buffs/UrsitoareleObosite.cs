using Terraria;
using Terraria.ModLoader;

namespace SummonerReborn.Content.Buffs
{
	// Arată cât mai durează până când ursitoarele te pot salva iar de la moarte (doar în Mod OP).
	public class UrsitoareleObosite : ModBuff
	{
		public override void SetStaticDefaults() {
			Main.debuff[Type] = true;                       // e un debuff (nu-l poți da jos cu click dreapta)
			Terraria.ID.BuffID.Sets.NurseCannotRemoveDebuff[Type] = true; // nici Nurse nu-l scoate
		}
	}
}
