using Terraria;
using Terraria.ID;
using Terraria.ModLoader;

namespace SummonerReborn.Content.Buffs
{
	// Marca pusă de Biciul de Spini pe inamici. Minionii fac TagDamage în plus pe inamicii marcați.
	public class MarcaSpinilor : ModBuff
	{
		public static readonly int TagDamage = 5;

		public override void SetStaticDefaults() {
			BuffID.Sets.IsATagBuff[Type] = true; // merge și pe inamicii imuni la debuff-uri
		}
	}

	// Aplică efectul mărcii când un minion lovește un inamic marcat.
	public class MarcaSpinilorNPC : GlobalNPC
	{
		public override void ModifyHitByProjectile(NPC npc, Projectile projectile, ref NPC.HitModifiers modifiers) {
			if (projectile.npcProj || projectile.trap || !projectile.IsMinionOrSentryRelated)
				return;

			if (npc.HasBuff<MarcaSpinilor>()) {
				// Unii minioni din vanilla primesc doar o parte din tag damage, pentru balans.
				float multiplicator = ProjectileID.Sets.SummonTagDamageMultiplier[projectile.type];
				modifiers.FlatBonusDamage += MarcaSpinilor.TagDamage * multiplicator;
			}
		}
	}
}
