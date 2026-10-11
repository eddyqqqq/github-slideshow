using Microsoft.Xna.Framework;
using SummonerReborn.Content.Buffs;
using System;
using Terraria;
using Terraria.ID;
using Terraria.ModLoader;

namespace SummonerReborn.Content.Projectiles
{
	// Minionul Licurici: zboară prin blocuri și lovește inamicii prin contact.
	// Construit după ExampleSimpleMinion din ExampleMod-ul oficial, plus o lumină caldă.
	public class Licurici : ModProjectile
	{
		public override void SetStaticDefaults() {
			Main.projFrames[Type] = 4;                                // 4 cadre de animație în imagine
			ProjectileID.Sets.MinionTargettingFeature[Type] = true;   // poți alege ținta cu click dreapta
			Main.projPet[Type] = true;
			ProjectileID.Sets.MinionSacrificable[Type] = true;
			ProjectileID.Sets.CultistIsResistantTo[Type] = true;
		}

		public override void SetDefaults() {
			Projectile.width = 16;
			Projectile.height = 16;
			Projectile.tileCollide = false;
			Projectile.friendly = true;
			Projectile.minion = true;
			Projectile.DamageType = DamageClass.Summon;
			Projectile.minionSlots = 1f;
			Projectile.penetrate = -1;

			// Fiecare licurici își are propriul timp de invulnerabilitate pe inamic, ca mai mulți să poată lovi deodată.
			Projectile.usesLocalNPCImmunity = true;
			Projectile.localNPCHitCooldown = 20;
		}

		public override bool? CanCutTiles() => false;

		public override bool MinionContactDamage() => true;

		public override void AI() {
			Player owner = Main.player[Projectile.owner];

			if (!CheckActive(owner))
				return;

			GeneralBehavior(owner, out Vector2 vectorToIdlePosition, out float distanceToIdlePosition);
			SearchForTargets(owner, out bool foundTarget, out float distanceFromTarget, out Vector2 targetCenter);
			Movement(foundTarget, distanceFromTarget, targetCenter, distanceToIdlePosition, vectorToIdlePosition);
			Visuals();
		}

		// Minionul trăiește cât timp jucătorul e viu și are buff-ul.
		private bool CheckActive(Player owner) {
			if (owner.dead || !owner.active) {
				owner.ClearBuff(ModContent.BuffType<LicuriciBuff>());
				return false;
			}

			if (owner.HasBuff(ModContent.BuffType<LicuriciBuff>()))
				Projectile.timeLeft = 2;

			return true;
		}

		// Unde stă licuriciul când n-are ce ataca: deasupra și în spatele jucătorului.
		private void GeneralBehavior(Player owner, out Vector2 vectorToIdlePosition, out float distanceToIdlePosition) {
			Vector2 idlePosition = owner.Center;
			idlePosition.Y -= 48f;
			idlePosition.X += (10 + Projectile.minionPos * 40) * -owner.direction;

			vectorToIdlePosition = idlePosition - Projectile.Center;
			distanceToIdlePosition = vectorToIdlePosition.Length();

			// Dacă a rămas prea departe, se teleportează la jucător.
			if (Main.myPlayer == owner.whoAmI && distanceToIdlePosition > 2000f) {
				Projectile.position = idlePosition;
				Projectile.velocity *= 0.1f;
				Projectile.netUpdate = true;
			}

			// Licuricii se împing ușor unul pe altul, ca să nu stea unul peste altul.
			const float overlapVelocity = 0.04f;
			foreach (var other in Main.ActiveProjectiles) {
				if (other.whoAmI != Projectile.whoAmI && other.owner == Projectile.owner && Math.Abs(Projectile.position.X - other.position.X) + Math.Abs(Projectile.position.Y - other.position.Y) < Projectile.width) {
					Projectile.velocity.X += Projectile.position.X < other.position.X ? -overlapVelocity : overlapVelocity;
					Projectile.velocity.Y += Projectile.position.Y < other.position.Y ? -overlapVelocity : overlapVelocity;
				}
			}
		}

		// Alege ținta: întâi ținta aleasă de jucător (sau boss-ul, vezi InvocatorPlayer), altfel cel mai apropiat inamic.
		private void SearchForTargets(Player owner, out bool foundTarget, out float distanceFromTarget, out Vector2 targetCenter) {
			distanceFromTarget = 700f;
			targetCenter = Projectile.position;
			foundTarget = false;

			if (owner.HasMinionAttackTargetNPC) {
				NPC npc = Main.npc[owner.MinionAttackTargetNPC];
				float between = Vector2.Distance(npc.Center, Projectile.Center);
				if (between < 2000f) {
					distanceFromTarget = between;
					targetCenter = npc.Center;
					foundTarget = true;
				}
			}

			if (!foundTarget) {
				foreach (var npc in Main.ActiveNPCs) {
					if (!npc.CanBeChasedBy())
						continue;

					float between = Vector2.Distance(npc.Center, Projectile.Center);
					bool closest = Vector2.Distance(Projectile.Center, targetCenter) > between;
					bool inRange = between < distanceFromTarget;
					bool lineOfSight = Collision.CanHitLine(Projectile.position, Projectile.width, Projectile.height, npc.position, npc.width, npc.height);
					bool closeThroughWall = between < 100f;

					if (((closest && inRange) || !foundTarget) && (lineOfSight || closeThroughWall)) {
						distanceFromTarget = between;
						targetCenter = npc.Center;
						foundTarget = true;
					}
				}
			}

			// Face damage doar când are țintă (ca să nu lovească manechinele cât stă degeaba).
			Projectile.friendly = foundTarget;
		}

		private void Movement(bool foundTarget, float distanceFromTarget, Vector2 targetCenter, float distanceToIdlePosition, Vector2 vectorToIdlePosition) {
			float speed = 10f;   // puțin mai rapid decât minionul din ExampleMod
			float inertia = 18f;

			if (foundTarget) {
				if (distanceFromTarget > 40f) {
					Vector2 direction = targetCenter - Projectile.Center;
					direction.Normalize();
					direction *= speed;
					Projectile.velocity = (Projectile.velocity * (inertia - 1) + direction) / inertia;
				}
			}
			else {
				if (distanceToIdlePosition > 600f) {
					speed = 12f;
					inertia = 60f;
				}
				else {
					speed = 4f;
					inertia = 80f;
				}

				if (distanceToIdlePosition > 20f) {
					vectorToIdlePosition.Normalize();
					vectorToIdlePosition *= speed;
					Projectile.velocity = (Projectile.velocity * (inertia - 1) + vectorToIdlePosition) / inertia;
				}
				else if (Projectile.velocity == Vector2.Zero) {
					Projectile.velocity.X = -0.15f;
					Projectile.velocity.Y = -0.05f;
				}
			}
		}

		private void Visuals() {
			Projectile.rotation = Projectile.velocity.X * 0.05f;

			// Animația: trece prin cele 4 cadre, pe rând.
			if (++Projectile.frameCounter >= 5) {
				Projectile.frameCounter = 0;
				Projectile.frame = (Projectile.frame + 1) % Main.projFrames[Type];
			}

			// Lumină galben-verzuie, ca un licurici adevărat.
			Lighting.AddLight(Projectile.Center, new Vector3(0.9f, 0.85f, 0.3f) * 0.8f);

			if (Main.rand.NextBool(12)) {
				Dust d = Dust.NewDustDirect(Projectile.position, Projectile.width, Projectile.height, DustID.Firefly);
				d.noGravity = true;
				d.velocity *= 0.2f;
			}
		}
	}
}
