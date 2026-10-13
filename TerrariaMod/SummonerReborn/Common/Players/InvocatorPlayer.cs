using Microsoft.Xna.Framework;
using SummonerReborn.Common.Configs;
using SummonerReborn.Content.Buffs;
using Terraria;
using Terraria.ID;
using Terraria.ModLoader;

namespace SummonerReborn.Common.Players
{
	// Aici sunt efectele care țin de jucător: accesoriile, bonusul de set și țintirea automată pe boss.
	// Itemele doar „aprind” câte un bool în fiecare frame (vezi UpdateAccessory / UpdateArmorSet).
	public class InvocatorPlayer : ModPlayer
	{
		// Setate de iteme în fiecare frame. ResetEffects le stinge, ca să fie active doar cât porți itemul.
		public bool Martisor;
		public bool TalismanulDeUsturoi;
		public bool SetUrsitoare;

		// Câte frame-uri (60 = 1 secundă) a stat jucătorul nemișcat. Folosit de bonusul de set.
		public int TimpNemiscat;

		public const int TimpNecesarNemiscat = 120;       // 2 secunde
		public const float BonusDamageNemiscat = 0.25f;   // +25% damage de summon
		public const int SansaMartisor = 4;               // 1 din 4 lovituri de minion
		public const int DurataPrimavara = 180;           // 3 secunde
		public const int DurataFurie = 300;               // 5 secunde
		public const int InvulnerabilitateTalisman = 30;  // +0.5 secunde

		public override void ResetEffects() {
			Martisor = false;
			TalismanulDeUsturoi = false;
			SetUrsitoare = false;
		}

		// Rulează după ce jocul a verificat bonusurile de set (UpdateArmorSet), deci SetUrsitoare e corect aici.
		public override void PostUpdateMiscEffects() {
			// Bonusul setului Ursitoarei: stai nemișcat 2 secunde -> minionii fac +25% damage.
			if (!SetUrsitoare) {
				TimpNemiscat = 0;
				return;
			}

			if (Player.velocity.LengthSquared() < 0.01f)
				TimpNemiscat++;
			else
				TimpNemiscat = 0;

			if (TimpNemiscat >= TimpNecesarNemiscat) {
				Player.GetDamage(DamageClass.Summon) += BonusDamageNemiscat;

				// Câteva scântei aurii, ca să vezi că bonusul e activ.
				if (Main.rand.NextBool(6)) {
					Dust d = Dust.NewDustDirect(Player.position, Player.width, Player.height, DustID.GoldFlame);
					d.noGravity = true;
					d.velocity *= 0.3f;
				}
			}
		}

		public override void OnHitNPCWithProj(Projectile proj, NPC target, NPC.HitInfo hit, int damageDone) {
			// Mărțișorul: loviturile minionilor îți dau uneori buff-ul Primăvară (regenerare).
			if (Martisor && proj.IsMinionOrSentryRelated && Main.rand.NextBool(SansaMartisor))
				Player.AddBuff(ModContent.BuffType<Primavara>(), DurataPrimavara);
		}

		public override void PostHurt(Player.HurtInfo info) {
			// Talismanul de Usturoi: când ești lovit, minionii intră în Furie și primești puțină invulnerabilitate în plus.
			if (!TalismanulDeUsturoi || info.PvP)
				return;

			Player.AddBuff(ModContent.BuffType<Furie>(), DurataFurie);
			Player.AddImmuneTime(info.CooldownCounter, InvulnerabilitateTalisman);
		}

		public override void PreUpdate() {
			// Țintirea automată pe boss. Merge și pentru minionii din vanilla, pentru că folosește
			// același mecanism ca atunci când alegi tu ținta cu click dreapta pe un toiag de summon.
			if (Player.whoAmI != Main.myPlayer || !ModContent.GetInstance<ConfigTestare>().TintireAutomataBoss)
				return;

			// Dacă ai ales deja o țintă (cu click dreapta sau cu biciul), o respectăm.
			if (Player.HasMinionAttackTargetNPC)
				return;

			NPC celMaiApropiatBoss = null;
			float distantaMinima = 2000f; // cam un ecran și jumătate

			foreach (NPC npc in Main.ActiveNPCs) {
				if (!npc.boss || !npc.CanBeChasedBy())
					continue;

				float distanta = Vector2.Distance(npc.Center, Player.Center);
				if (distanta < distantaMinima) {
					distantaMinima = distanta;
					celMaiApropiatBoss = npc;
				}
			}

			if (celMaiApropiatBoss != null)
				Player.MinionAttackTargetNPC = celMaiApropiatBoss.whoAmI;
		}
	}
}
