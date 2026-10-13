using SummonerReborn.Content.Buffs;
using Terraria;
using Terraria.ID;
using Terraria.ModLoader;

namespace SummonerReborn.Common.GlobalNPCs
{
	// Aplică efectul mărcilor de bici când un minion (sau ce trage un minion) lovește un inamic marcat.
	public class MarciBiciNPC : GlobalNPC
	{
		public override void ModifyHitByProjectile(NPC npc, Projectile projectile, ref NPC.HitModifiers modifiers) {
			if (projectile.npcProj || projectile.trap || !projectile.IsMinionOrSentryRelated)
				return;

			// Unii minioni din vanilla primesc doar o parte din tag damage, pentru balans.
			float multiplicator = ProjectileID.Sets.SummonTagDamageMultiplier[projectile.type];

			if (npc.HasBuff<MarcaSpinilor>())
				modifiers.FlatBonusDamage += MarcaSpinilor.TagDamage * multiplicator;

			if (npc.HasBuff<MarcaZmeului>())
				modifiers.FlatBonusDamage += MarcaZmeului.TagDamage * multiplicator;

			if (npc.HasBuff<MarcaFurtunii>()) {
				modifiers.FlatBonusDamage += MarcaFurtunii.TagDamage * multiplicator;

				// Șansă în plus de lovitură critică (ca la biciurile late game din vanilla).
				if (Main.rand.Next(100) < MarcaFurtunii.SansaCritica)
					modifiers.SetCrit();
			}
		}
	}
}
