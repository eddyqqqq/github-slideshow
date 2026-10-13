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
	// Ideea: itemele de început din mod cresc odată cu progresia (fiecare boss învins le face mai puternice),
	// iar jucătorul primește destulă supraviețuire ca Master Mode să fie ușor, dar nu banal.
	// Toate cifrele sunt aici sus, ca să le poți regla ușor.
	public class ModOPPlayer : ModPlayer
	{
		// --- Bonusuri generale ---
		public const float DamageSummon = 0.40f;        // +40% damage de summon
		public const int MinioniInPlus = 2;
		public const int SantineleInPlus = 1;
		public const int Aparare = 12;
		public const float ReducereDamage = 0.15f;      // primești cu 15% mai puțin damage
		public const int ViataMaximaInPlus = 60;
		public const int Regenerare = 4;                // jumătăți de HP pe secundă: 4 = 2 HP/s
		public const float VitezaMiscare = 0.10f;
		public const float VitezaBici = 0.20f;          // biciurile lovesc cu 20% mai repede

		// --- Scalare cu progresia (pentru itemele din mod) ---
		public const float DamagePerBoss = 0.30f;       // armele din mod: +30% damage pentru fiecare boss învins
		public const int AparareSetPerBoss = 2;         // setul Ursitoarei: +2 apărare pentru fiecare boss învins
		public const int BossiPerMinionSet = 4;         // setul Ursitoarei: +1 minion la fiecare 4 boss-i învinși

		// --- Furtul de viață al minionilor ---
		public const float FurtViata = 0.03f;           // 3% din damage-ul unei lovituri de minion
		public const int FurtViataMaxim = 4;            // maxim 4 HP pe vindecare
		public const int PauzaFurtViata = 20;           // maxim 3 vindecări pe secundă

		// --- Salvarea de la moarte ---
		public const int PauzaSalvare = 60 * 60 * 3;    // o dată la 3 minute

		private int pauzaFurtViata;

		public static bool Activ => ModContent.GetInstance<ConfigTestare>().ModOP;

		// Câți boss-i au fost învinși în lumea asta (0-17). Folosit pentru scalare.
		public static int BossiInvinsi() {
			bool[] bossi = {
				NPC.downedSlimeKing, NPC.downedBoss1, NPC.downedBoss2, NPC.downedQueenBee, NPC.downedDeerclops,
				NPC.downedBoss3, Main.hardMode, NPC.downedQueenSlime, NPC.downedMechBoss1, NPC.downedMechBoss2,
				NPC.downedMechBoss3, NPC.downedPlantBoss, NPC.downedGolemBoss, NPC.downedFishron,
				NPC.downedEmpressOfLight, NPC.downedAncientCultist, NPC.downedMoonlord,
			};

			int numar = 0;
			foreach (bool invins in bossi) {
				if (invins)
					numar++;
			}
			return numar;
		}

		// Multiplicatorul de damage pentru armele din mod (1 la început, ~6 după Moon Lord).
		public static float MultiplicatorArme() => 1f + DamagePerBoss * BossiInvinsi();

		public override void OnEnterWorld() {
			if (Player.whoAmI == Main.myPlayer && Activ)
				Main.NewText($"Renasterea Invocatorului: Mod OP activ. Armele din mod fac acum x{MultiplicatorArme():0.0} damage.", Color.Gold);
		}

		public override void PostUpdateEquips() {
			if (!Activ)
				return;

			Player.GetDamage(DamageClass.Summon) += DamageSummon;
			Player.GetAttackSpeed(DamageClass.SummonMeleeSpeed) += VitezaBici;
			Player.maxMinions += MinioniInPlus;
			Player.maxTurrets += SantineleInPlus;
			Player.statDefense += Aparare;
			Player.endurance += ReducereDamage;
			Player.statLifeMax2 += ViataMaximaInPlus;
			Player.lifeRegen += Regenerare;
			Player.moveSpeed += VitezaMiscare;

			if (pauzaFurtViata > 0)
				pauzaFurtViata--;
		}

		// Rulează după ce jocul a verificat bonusurile de set, deci știm sigur dacă porți setul Ursitoarei.
		public override void PostUpdateMiscEffects() {
			if (!Activ || !Player.GetModPlayer<InvocatorPlayer>().SetUrsitoare)
				return;

			// Setul Ursitoarei crește odată cu progresia.
			int bossi = BossiInvinsi();
			Player.statDefense += AparareSetPerBoss * bossi;
			Player.maxMinions += bossi / BossiPerMinionSet;
		}

		public override void OnHitNPCWithProj(Projectile proj, NPC target, NPC.HitInfo hit, int damageDone) {
			// Minionii fură viață pentru tine. Nu merge pe manechine (Target Dummy) și pe creaturi inofensive.
			if (!Activ || !proj.IsMinionOrSentryRelated || pauzaFurtViata > 0 || target.immortal || target.friendly || target.lifeMax <= 5)
				return;

			int vindecare = Math.Clamp((int)(damageDone * FurtViata), 1, FurtViataMaxim);
			Player.Heal(vindecare);
			pauzaFurtViata = PauzaFurtViata;
		}

		public override bool PreKill(double damage, int hitDirection, bool pvp, ref bool playSound, ref bool genDust, ref PlayerDeathReason damageSource) {
			// Ursitoarele te salvează de la moarte o dată la 3 minute.
			if (!Activ || pvp || Player.HasBuff<UrsitoareleObosite>())
				return true; // mori normal

			Player.statLife = Player.statLifeMax2 / 3;
			Player.SetImmuneTimeForAllTypes(120); // 2 secunde de invulnerabilitate
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
