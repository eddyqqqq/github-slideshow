using Terraria;
using Terraria.ModLoader;

namespace SummonerReborn.Content.Buffs
{
	// Dat de Biciul Solomonarului când lovești: minionii fac mai mult damage.
	// Ca să-l păstrezi, trebuie să tot lovești cu biciul (sinergie bici + minioni).
	public class ChemareaSolomonarului : ModBuff
	{
		public static readonly float BonusDamage = 0.15f; // +15% damage de summon
		public static readonly int Durata = 300;          // 5 secunde

		public override void Update(Player player, ref int buffIndex) {
			player.GetDamage(DamageClass.Summon) += BonusDamage;
		}
	}
}
