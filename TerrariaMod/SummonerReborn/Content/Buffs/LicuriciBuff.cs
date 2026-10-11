using SummonerReborn.Content.Projectiles;
using Terraria;
using Terraria.ModLoader;

namespace SummonerReborn.Content.Buffs
{
	// Iconița de buff a licuricilor. Cât timp există licurici, buff-ul rămâne; click dreapta pe el îi trimite acasă.
	public class LicuriciBuff : ModBuff
	{
		public override void SetStaticDefaults() {
			Main.buffNoSave[Type] = true;
			Main.buffNoTimeDisplay[Type] = true;
		}

		public override void Update(Player player, ref int buffIndex) {
			if (player.ownedProjectileCounts[ModContent.ProjectileType<Licurici>()] > 0) {
				player.buffTime[buffIndex] = 18000;
			}
			else {
				player.DelBuff(buffIndex);
				buffIndex--;
			}
		}
	}
}
