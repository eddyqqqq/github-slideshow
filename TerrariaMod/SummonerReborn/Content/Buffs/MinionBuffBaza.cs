using Terraria;
using Terraria.ModLoader;

namespace SummonerReborn.Content.Buffs
{
	// Bază pentru buff-urile de minion: buff-ul rămâne cât timp există minionul.
	// Click dreapta pe iconiță îți trimite minionii acasă.
	public abstract class MinionBuffBaza : ModBuff
	{
		protected abstract int Minion { get; }

		public override void SetStaticDefaults() {
			Main.buffNoSave[Type] = true;
			Main.buffNoTimeDisplay[Type] = true;
		}

		public override void Update(Player player, ref int buffIndex) {
			if (player.ownedProjectileCounts[Minion] > 0) {
				player.buffTime[buffIndex] = 18000;
			}
			else {
				player.DelBuff(buffIndex);
				buffIndex--;
			}
		}
	}
}
