package com.photonbounce.leads.fragments

import android.graphics.Color
import android.os.Bundle
import android.view.LayoutInflater
import android.view.View
import android.view.ViewGroup
import androidx.fragment.app.Fragment
import androidx.lifecycle.ViewModelProvider
import com.github.mikephil.charting.components.XAxis
import com.github.mikephil.charting.data.*
import com.github.mikephil.charting.formatter.IndexAxisValueFormatter
import com.photonbounce.leads.LeadsApplication
import com.photonbounce.leads.R
import com.photonbounce.leads.databinding.FragmentAnalyticsBinding
import com.photonbounce.leads.viewmodels.AnalyticsViewModel
import com.photonbounce.leads.viewmodels.ViewModelFactory

class AnalyticsFragment : Fragment() {

    private var _binding: FragmentAnalyticsBinding? = null
    private val binding get() = _binding!!
    private lateinit var viewModel: AnalyticsViewModel

    override fun onCreateView(
        inflater: LayoutInflater, container: ViewGroup?,
        savedInstanceState: Bundle?
    ): View {
        _binding = FragmentAnalyticsBinding.inflate(inflater, container, false)
        return binding.root
    }

    override fun onViewCreated(view: View, savedInstanceState: Bundle?) {
        super.onViewCreated(view, savedInstanceState)

        val app = requireActivity().application as LeadsApplication
        val factory = ViewModelFactory(app.api, app.database, app.tokenManager, app.networkMonitor)
        viewModel = ViewModelProvider(this, factory)[AnalyticsViewModel::class.java]

        if (!viewModel.isVip) {
            showVipLock()
        } else {
            showAnalytics()
            viewModel.loadAnalytics()
        }

        viewModel.analytics.observe(viewLifecycleOwner) { data ->
            setupBarChart(data)
            setupPieChart(data)
        }
    }

    private fun showVipLock() {
        binding.vipLockOverlay.visibility = View.VISIBLE
        binding.chartContainer.visibility = View.GONE
        binding.btnUpgrade.setOnClickListener {
            // Navigate to settings or billing flow
        }
    }

    private fun showAnalytics() {
        binding.vipLockOverlay.visibility = View.GONE
        binding.chartContainer.visibility = View.VISIBLE
    }

    private fun setupBarChart(data: com.photonbounce.leads.models.AnalyticsData) {
        val entries = data.leadTrends.mapIndexed { index, point ->
            BarEntry(index.toFloat(), point.count.toFloat())
        }

        val dataSet = BarDataSet(entries, "Leads").apply {
            color = Color.parseColor("#00D4FF")
            valueTextColor = Color.WHITE
        }

        binding.barChart.data = BarData(dataSet)
        binding.barChart.xAxis.apply {
            valueFormatter = IndexAxisValueFormatter(data.leadTrends.map { it.date })
            position = XAxis.XAxisPosition.BOTTOM
            textColor = Color.WHITE
            granularity = 1f
        }
        binding.barChart.axisLeft.textColor = Color.WHITE
        binding.barChart.axisRight.isEnabled = false
        binding.barChart.description.isEnabled = false
        binding.barChart.legend.textColor = Color.WHITE
        binding.barChart.invalidate()
    }

    private fun setupPieChart(data: com.photonbounce.leads.models.AnalyticsData) {
        val entries = data.statusBreakdown.map {
            PieEntry(it.count.toFloat(), it.status.replaceFirstChar { c -> c.uppercase() })
        }

        val colors = listOf(
            Color.parseColor("#00D4FF"),
            Color.parseColor("#7B61FF"),
            Color.parseColor("#F59E0B"),
            Color.parseColor("#10B981"),
            Color.parseColor("#EC4899"),
            Color.parseColor("#EF4444"),
            Color.parseColor("#6B7280")
        )

        val dataSet = PieDataSet(entries, "Status").apply {
            this.colors = colors
            valueTextColor = Color.WHITE
        }

        binding.pieChart.data = PieData(dataSet)
        binding.pieChart.description.isEnabled = false
        binding.pieChart.legend.textColor = Color.WHITE
        binding.pieChart.invalidate()
    }

    override fun onDestroyView() {
        super.onDestroyView()
        _binding = null
    }
}
