using Terraria;
using Terraria.ModLoader;

namespace SummonerReborn.Content.Buffs
{
	// Buff dat de Talismanul de Usturoi când ești lovit: minionii fac mai mult damage.
	public class Furie : ModBuff
	{
		public static readonly float BonusDamage = 0.20f; // +20% damage de summon

		public override void Update(Player player, ref int buffIndex) {
			player.GetDamage(DamageClass.Summon) += BonusDamage;
		}
	}
}
