using Terraria;
using Terraria.ID;
using Terraria.ModLoader;

namespace SummonerReborn.Content.Buffs
{
	// Mărcile puse de biciurile din mod pe inamici („tag”-uri).
	// Minionii fac TagDamage în plus pe inamicii marcați. Efectul e aplicat în Common/GlobalNPCs/MarciBiciNPC.cs.

	// Biciul de Spini (early game)
	public class MarcaSpinilor : ModBuff
	{
		public static readonly int TagDamage = 5;

		public override void SetStaticDefaults() {
			BuffID.Sets.IsATagBuff[Type] = true; // merge și pe inamicii imuni la debuff-uri
		}
	}

	// Biciul Zmeului (Hardmode, după boss-ii mecanici)
	public class MarcaZmeului : ModBuff
	{
		public static readonly int TagDamage = 8;

		public override void SetStaticDefaults() {
			BuffID.Sets.IsATagBuff[Type] = true;
		}
	}

	// Biciul Solomonarului (late game)
	public class MarcaFurtunii : ModBuff
	{
		public static readonly int TagDamage = 14;
		public static readonly int SansaCritica = 10; // % șansă de lovitură critică în plus pentru minioni

		public override void SetStaticDefaults() {
			BuffID.Sets.IsATagBuff[Type] = true;
		}
	}
}
