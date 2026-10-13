using SummonerReborn.Content.Projectiles;
using Terraria.ModLoader;

namespace SummonerReborn.Content.Buffs
{
	// Buff-urile minionilor din mod (fiecare ține în viață minionul lui).
	public class LicuriciBuff : MinionBuffBaza
	{
		protected override int Minion => ModContent.ProjectileType<Licurici>();
	}

	public class IeleBuff : MinionBuffBaza
	{
		protected override int Minion => ModContent.ProjectileType<Iele>();
	}

	public class StrigoiBuff : MinionBuffBaza
	{
		protected override int Minion => ModContent.ProjectileType<Strigoi>();
	}

	public class NorisorBuff : MinionBuffBaza
	{
		protected override int Minion => ModContent.ProjectileType<Norisor>();
	}
}
