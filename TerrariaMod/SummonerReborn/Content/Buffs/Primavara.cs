using Terraria;
using Terraria.ModLoader;

namespace SummonerReborn.Content.Buffs
{
	// Buff dat de Mărțișor: regenerare de viață.
	public class Primavara : ModBuff
	{
		// lifeRegen e în jumătăți de HP pe secundă: 8 = 4 HP pe secundă.
		public static readonly int Regenerare = 8;

		public override void Update(Player player, ref int buffIndex) {
			player.lifeRegen += Regenerare;
		}
	}
}
