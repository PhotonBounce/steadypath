package com.photonbounce.leads.adapters

import android.view.LayoutInflater
import android.view.ViewGroup
import androidx.recyclerview.widget.DiffUtil
import androidx.recyclerview.widget.ListAdapter
import androidx.recyclerview.widget.RecyclerView
import com.photonbounce.leads.databinding.ItemMicrositeBinding
import com.photonbounce.leads.db.MicrositeEntity

class MicrositesAdapter(
    private val onToggle: (String, Boolean) -> Unit
) : ListAdapter<MicrositeEntity, MicrositesAdapter.ViewHolder>(DiffCallback()) {

    override fun onCreateViewHolder(parent: ViewGroup, viewType: Int): ViewHolder {
        val binding = ItemMicrositeBinding.inflate(
            LayoutInflater.from(parent.context), parent, false
        )
        return ViewHolder(binding)
    }

    override fun onBindViewHolder(holder: ViewHolder, position: Int) {
        holder.bind(getItem(position))
    }

    inner class ViewHolder(private val binding: ItemMicrositeBinding) :
        RecyclerView.ViewHolder(binding.root) {

        fun bind(microsite: MicrositeEntity) {
            binding.tvName.text = microsite.name
            binding.tvSlug.text = "@${microsite.slug}"
            binding.tvNiche.text = microsite.niche ?: "General"
            binding.tvLeads.text = "${microsite.leadCount} leads"
            binding.switchActive.isChecked = microsite.active

            binding.switchActive.setOnCheckedChangeListener { _, isChecked ->
                onToggle(microsite.id, isChecked)
            }
        }
    }

    class DiffCallback : DiffUtil.ItemCallback<MicrositeEntity>() {
        override fun areItemsTheSame(old: MicrositeEntity, new: MicrositeEntity) = old.id == new.id
        override fun areContentsTheSame(old: MicrositeEntity, new: MicrositeEntity) = old == new
    }
}
