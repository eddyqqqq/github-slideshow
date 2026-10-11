using SummonerReborn.Common.Players;
using Terraria;
using Terraria.ID;
using Terraria.Localization;
using Terraria.ModLoader;

namespace SummonerReborn.Content.Items.Armor
{
	// Setul Ursitoarei, piesa de cap. Aici e definit și bonusul de set (pentru toate cele 3 piese).
	// Comparație vanilla: Bee armor (cel mai bun set de summoner înainte de Hardmode) are 13 apărare și +2 sloturi.
	// Setul Ursitoarei vine mai devreme (din argint/tungsten) și e, intenționat, mai puternic pentru faza de test.
	[AutoloadEquip(EquipType.Head)] // folosește GlugaUrsitoarei_Head.png pentru cum arată pe personaj
	public class GlugaUrsitoarei : ModItem
	{
		public static readonly int MinioniInPlus = 1;
		public static readonly int DamageSummon = 5; // %

		public static LocalizedText SetBonusText { get; private set; }

		public override LocalizedText Tooltip => base.Tooltip.WithFormatArgs(MinioniInPlus, DamageSummon);

		public override void SetStaticDefaults() {
			SetBonusText = this.GetLocalization("SetBonus").WithFormatArgs((int)(InvocatorPlayer.BonusDamageNemiscat * 100));
		}

		public override void SetDefaults() {
			Item.width = 22;
			Item.height = 20;
			Item.value = Item.sellPrice(silver: 60);
			Item.rare = ItemRarityID.Blue;
			Item.defense = 4;
		}

		public override void UpdateEquip(Player player) {
			player.maxMinions += MinioniInPlus;
			player.GetDamage(DamageClass.Summon) += DamageSummon / 100f;
		}

		// Bonusul de set e activ doar dacă porți și mantia, și pantalonii.
		public override bool IsArmorSet(Item head, Item body, Item legs) {
			return body.type == ModContent.ItemType<MantiaUrsitoarei>() && legs.type == ModContent.ItemType<PantaloniiUrsitoarei>();
		}

		public override void UpdateArmorSet(Player player) {
			player.setBonus = SetBonusText.Value;
			player.GetModPlayer<InvocatorPlayer>().SetUrsitoare = true;
		}

		public override void AddRecipes() {
			// Merge și cu argint, și cu tungsten (depinde ce minereu are lumea ta).
			foreach (int bara in new[] { ItemID.SilverBar, ItemID.TungstenBar }) {
				CreateRecipe()
					.AddIngredient(bara, 8)
					.AddIngredient(ItemID.Silk, 5)
					.AddTile(TileID.Anvils)
					.Register();
			}
		}
	}
}
