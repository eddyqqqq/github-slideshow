using Microsoft.Xna.Framework;
using SummonerReborn.Common.Configs;
using SummonerReborn.Content.Buffs;
using System;
using Terraria;
using Terraria.DataStructures;
using Terraria.ID;
using Terraria.ModLoader;

namespace SummonerReborn.Common.Players
{
	// Varianta OP („Binecuvantarea Ursitoarelor”). Se pornește din setările mod-ului: Mod OP.
	// Scopul: Master Mode mai ușor pentru summoner, dar cu progresie normală.
	// Itemele NU cresc cu progresia: tot trebuie să-ți faci echipament nou pentru fiecare etapă.
	// Bonusurile sunt moderate, cam cât ar da o armură și un accesoriu în plus. Toate cifrele sunt aici sus.
	public class ModOPPlayer : ModPlayer
	{
		// --- Bonusuri generale ---
		public const float DamageSummon = 0.15f;        // +15% damage de summon
		public const int MinioniInPlus = 1;
		public const int Aparare = 6;
		public const float ReducereDamage = 0.10f;      // primești cu 10% mai puțin damage
		public const int ViataMaximaInPlus = 40;
		public const int Regenerare = 2;                // jumătăți de HP pe secundă: 2 = 1 HP/s
		public const float VitezaBici = 0.10f;          // biciurile lovesc cu 10% mai repede

		// --- Furtul de viață al minionilor (summonerul nu are altfel healing) ---
		public const float FurtViata = 0.02f;           // 2% din damage-ul unei lovituri de minion
		public const int FurtViataMaxim = 2;            // maxim 2 HP pe vindecare
		public const int PauzaFurtViata = 30;           // maxim 2 vindecări pe secundă

		// --- Salvarea de la moarte ---
		public const int PauzaSalvare = 60 * 60 * 5;    // o dată la 5 minute
		public const int ViataDupaSalvare = 4;          // rămâi cu 1/4 din viață

		private int pauzaFurtViata;

		public static bool Activ => ModContent.GetInstance<ConfigTestare>().ModOP;

		public override void OnEnterWorld() {
			if (Player.whoAmI == Main.myPlayer && Activ)
				Main.NewText("Renasterea Invocatorului: Mod OP activ (Binecuvantarea Ursitoarelor).", Color.Gold);
		}

		public override void PostUpdateEquips() {
			if (!Activ)
				return;

			Player.GetDamage(DamageClass.Summon) += DamageSummon;
			Player.GetAttackSpeed(DamageClass.SummonMeleeSpeed) += VitezaBici;
			Player.maxMinions += MinioniInPlus;
			Player.statDefense += Aparare;
			Player.endurance += ReducereDamage;
			Player.statLifeMax2 += ViataMaximaInPlus;
			Player.lifeRegen += Regenerare;

			if (pauzaFurtViata > 0)
				pauzaFurtViata--;
		}

		public override void OnHitNPCWithProj(Projectile proj, NPC target, NPC.HitInfo hit, int damageDone) {
			// Minionii fură puțină viață pentru tine. Nu merge pe manechine (Target Dummy) și pe creaturi inofensive.
			if (!Activ || !proj.IsMinionOrSentryRelated || pauzaFurtViata > 0 || target.immortal || target.friendly || target.lifeMax <= 5)
				return;

			int vindecare = Math.Clamp((int)(damageDone * FurtViata), 1, FurtViataMaxim);
			Player.Heal(vindecare);
			pauzaFurtViata = PauzaFurtViata;
		}

		public override bool PreKill(double damage, int hitDirection, bool pvp, ref bool playSound, ref bool genDust, ref PlayerDeathReason damageSource) {
			// Ursitoarele te salvează de la moarte o dată la 5 minute.
			if (!Activ || pvp || Player.HasBuff<UrsitoareleObosite>())
				return true; // mori normal

			Player.statLife = Player.statLifeMax2 / ViataDupaSalvare;
			Player.SetImmuneTimeForAllTypes(90); // 1.5 secunde de invulnerabilitate
			Player.AddBuff(ModContent.BuffType<UrsitoareleObosite>(), PauzaSalvare);

			for (int i = 0; i < 40; i++) {
				Dust d = Dust.NewDustDirect(Player.position, Player.width, Player.height, DustID.GoldFlame);
				d.noGravity = true;
				d.velocity *= 3f;
			}

			if (Player.whoAmI == Main.myPlayer)
				Main.NewText("Ursitoarele te-au salvat de la moarte!", Color.Gold);

			return false; // nu mori
		}
	}
}
