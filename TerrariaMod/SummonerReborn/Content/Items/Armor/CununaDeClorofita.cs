using SummonerReborn.Common.Players;
using Terraria;
using Terraria.ID;
using Terraria.Localization;
using Terraria.ModLoader;

namespace SummonerReborn.Content.Items.Armor
{
	// MID HARDMODE. Casca de summoner pentru setul de Chlorophyte.
	// Se poartă cu Chlorophyte Plate Mail și Chlorophyte Greaves din vanilla, exact ca celelalte căști Chlorophyte
	// (vanilla are cască pentru melee, ranged și magic, dar nu și pentru summoner).
	// Comparație vanilla: Spooky armor (cel mai bun set de summoner înainte de Lunatic) +4 minioni în total.
	[AutoloadEquip(EquipType.Head)] // folosește CununaDeClorofita_Head.png
	public class CununaDeClorofita : ModItem
	{
		public static readonly int MinioniInPlus = 2;
		public static readonly int DamageSummon = 15; // %
		public static readonly int MinioniSet = 1;

		public static LocalizedText SetBonusText { get; private set; }

		public override LocalizedText Tooltip => base.Tooltip.WithFormatArgs(MinioniInPlus, DamageSummon);

		public override void SetStaticDefaults() {
			SetBonusText = this.GetLocalization("SetBonus").WithFormatArgs(MinioniSet);
		}

		public override void SetDefaults() {
			Item.width = 22;
			Item.height = 20;
			Item.value = Item.sellPrice(gold: 6);
			Item.rare = ItemRarityID.Lime;
			Item.defense = 10;
		}

		public override void UpdateEquip(Player player) {
			player.maxMinions += MinioniInPlus;
			player.GetDamage(DamageClass.Summon) += DamageSummon / 100f;
		}

		public override bool IsArmorSet(Item head, Item body, Item legs) {
			return body.type == ItemID.ChlorophytePlateMail && legs.type == ItemID.ChlorophyteGreaves;
		}

		public override void UpdateArmorSet(Player player) {
			player.setBonus = SetBonusText.Value;
			player.crystalLeaf = true;          // cristalul de frunze din setul Chlorophyte din vanilla
			player.maxMinions += MinioniSet;
		}

		public override void AddRecipes() {
			CreateRecipe()
				.AddIngredient(ItemID.ChlorophyteBar, 12)
				.AddTile(TileID.MythrilAnvil)
				.Register();
		}
	}
}
