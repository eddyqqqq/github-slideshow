using SummonerReborn.Content.Items.Weapons;
using Terraria;
using Terraria.GameContent.ItemDropRules;
using Terraria.ID;
using Terraria.ModLoader;

namespace SummonerReborn.Common.Systems
{
	// Pune Toiagul Strigoiului în Shadow Chests (cuferele încuiate din Underworld) când se generează o lume nouă.
	// Construit după ChestItemWorldGen din ExampleMod.
	public class StrigoiChestSystem : ModSystem
	{
		// Pe sprite-ul cuferelor din Terraria: index 3 = Shadow Chest, index 4 = Locked Shadow Chest.
		// 36 = lățimea unui cufăr în pixeli (cu spațiere). Le verificăm pe amândouă, ca să fim siguri.
		private const int StilShadowChest = 3;
		private const int StilShadowChestIncuiat = 4;

		public override void PostWorldGen() {
			int toiag = ModContent.ItemType<ToiagulStrigoiului>();

			for (int chestIndex = 0; chestIndex < Main.maxChests; chestIndex++) {
				Chest chest = Main.chest[chestIndex];
				if (chest == null)
					continue;

				Tile chestTile = Main.tile[chest.x, chest.y];
				bool esteShadowChest = chestTile.TileFrameX == StilShadowChest * 36 || chestTile.TileFrameX == StilShadowChestIncuiat * 36;
				if (chestTile.TileType != TileID.Containers || !esteShadowChest)
					continue;

				// Cam într-un cufăr din 3.
				if (!WorldGen.genRand.NextBool(3))
					continue;

				for (int slot = 0; slot < Chest.maxItems; slot++) {
					if (chest.item[slot].type == ItemID.None) {
						chest.item[slot].SetDefaults(toiag);
						break;
					}
				}
			}
		}
	}

	// Pentru lumile existente (unde cuferele sunt deja generate): Toiagul Strigoiului cade rar din Demoni.
	public class StrigoiDropNPC : GlobalNPC
	{
		public override void ModifyNPCLoot(NPC npc, NPCLoot npcLoot) {
			if (npc.type == NPCID.Demon || npc.type == NPCID.VoodooDemon)
				npcLoot.Add(ItemDropRule.Common(ModContent.ItemType<ToiagulStrigoiului>(), 35)); // 1 din 35
		}
	}
}
