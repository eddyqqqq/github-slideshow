namespace CookingBook
{
    /// <summary>Setările modului, salvate în config.json (creat automat la prima pornire).</summary>
    public sealed class ModConfig
    {
        /// <summary>Prețul cărții Cooking Book la Pierre.</summary>
        public int CookingBookPrice { get; set; } = 10_000;

        /// <summary>Prețul unei semințe de Mahogany (copac care dă hardwood) la Pierre.</summary>
        public int MahoganySeedPrice { get; set; } = 500;

        /// <summary>Câți cărbuni dă Charcoal Kiln pentru 1 lemn (Wood).</summary>
        public int CoalPerWood { get; set; } = 2;

        /// <summary>Câți cărbuni dă Charcoal Kiln pentru 1 hardwood.</summary>
        public int CoalPerHardwood { get; set; } = 10;

        /// <summary>Lemn în plus la tăierea oricărui copac mare.</summary>
        public int ExtraWoodPerTree { get; set; } = 10;

        /// <summary>Hardwood în plus la tăierea unui copac Mahogany mare.</summary>
        public int ExtraHardwoodPerMahogany { get; set; } = 10;

        /// <summary>Șansa zilnică de creștere a copacului Mahogany (în joc e 0.15; 1.0 = crește în fiecare zi).</summary>
        public float MahoganyGrowthChance { get; set; } = 0.5f;
    }
}
